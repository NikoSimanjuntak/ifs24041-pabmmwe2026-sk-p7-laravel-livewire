<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\MakesSpreadsheets;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use MakesSpreadsheets;
    use RefreshDatabase;

    private function service(): StockExcelService
    {
        return $this->app->make(StockExcelService::class);
    }

    private function toFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_export_writes_headers_and_rows(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->create([
            'name' => 'Beras', 'sku' => 'BR-1', 'quantity' => 10, 'unit' => 'kg',
            'price' => 12000, 'min_stock' => 3, 'status' => 'available', 'description' => 'Premium',
        ]);

        $rows = $this->readXlsx($this->service()->export(Stock::all()));

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertSame('Beras', $rows[1][0]);
        $this->assertSame('BR-1', $rows[1][1]);
        $this->assertEquals(10, $rows[1][2]);
        $this->assertSame('kg', $rows[1][3]);
        $this->assertEquals(12000, $rows[1][4]);
        $this->assertEquals(3, $rows[1][5]);
        $this->assertSame('available', $rows[1][6]);
        $this->assertSame('Premium', $rows[1][7]);
    }

    public function test_import_creates_stocks_and_normalizes_values(): void
    {
        $user = User::factory()->create();
        $path = $this->toFile($this->makeXlsx([
            ['Name', 'SKU', 'Quantity', 'Unit', 'Price', 'Min_Stock', 'Description'],
            ['Beras', 'BR-1', 10, 'kg', 12000, 3, 'Beras **premium**'],
            ['', 'X', 1, 'pcs', 1, 1, 'tanpa nama dilewati'],
            ['Gula', null, 'abc', null, 'n/a', null, null],
            ['Teh'],
        ]));

        $count = $this->service()->import($user, $path);
        unlink($path);

        $this->assertSame(3, $count);

        $beras = Stock::where('name', 'Beras')->firstOrFail();
        $this->assertSame($user->id, $beras->user_id);
        $this->assertSame(10, $beras->quantity);
        $this->assertSame('available', $beras->status->value);
        $this->assertSame('12000.00', $beras->price);
        $this->assertSame(3, $beras->min_stock);

        $gula = Stock::where('name', 'Gula')->firstOrFail();
        $this->assertNull($gula->sku);
        $this->assertNull($gula->description);
        $this->assertNull($gula->price);
        $this->assertSame(0, $gula->quantity);
        $this->assertSame('pcs', $gula->unit);
        $this->assertSame(5, $gula->min_stock);
        $this->assertSame('out_of_stock', $gula->status->value);
    }

    public function test_export_then_import_roundtrip_uses_qty_header(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();
        Stock::factory()->for($source)->count(2)->create();

        $path = $this->toFile($this->service()->export(Stock::where('user_id', $source->id)->get()));
        $count = $this->service()->import($target, $path);
        unlink($path);

        $this->assertSame(2, $count);
        $this->assertSame(2, Stock::where('user_id', $target->id)->count());
    }

    public function test_import_requires_name_column(): void
    {
        $path = $this->toFile($this->makeXlsx([['foo', 'bar'], [1, 2]]));

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->service()->import(User::factory()->create(), $path);
        } finally {
            unlink($path);
        }
    }

    public function test_import_rejects_non_spreadsheet_file(): void
    {
        $path = $this->toFile('ini bukan file excel');

        try {
            $this->expectException(\Exception::class);
            $this->service()->import(User::factory()->create(), $path);
        } finally {
            unlink($path);
        }
    }
}
