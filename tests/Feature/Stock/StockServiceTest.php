<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): StockService
    {
        return $this->app->make(StockService::class);
    }

    public function test_create_applies_defaults_and_status(): void
    {
        $user = User::factory()->create();

        $stock = $this->service()->create($user, ['name' => 'Kosong']);

        $this->assertSame(0, $stock->quantity);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame('pcs', $stock->unit);
        $this->assertSame('out_of_stock', $stock->status->value);
        $this->assertSame($user->id, $stock->user_id);
    }

    public function test_create_computes_each_status_and_stores_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $low = $this->service()->create($user, ['name' => 'A', 'quantity' => '3', 'min_stock' => '5', 'unit' => 'kg']);
        $ok = $this->service()->create($user, ['name' => 'B', 'quantity' => 50, 'min_stock' => 5], UploadedFile::fake()->image('c.jpg'));

        $this->assertSame('low_stock', $low->status->value);
        $this->assertSame('kg', $low->unit);
        $this->assertSame('available', $ok->status->value);
        Storage::disk('public')->assertExists($ok->cover_image_path);
    }

    public function test_update_recomputes_status_and_replaces_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $stock = $this->service()->create($user, ['name' => 'A', 'quantity' => 50, 'min_stock' => 5], UploadedFile::fake()->image('old.jpg'));
        $oldPath = $stock->cover_image_path;

        $updated = $this->service()->update($stock, ['name' => 'A2', 'quantity' => '2', 'min_stock' => '5'], UploadedFile::fake()->image('new.jpg'));

        $this->assertSame('A2', $updated->name);
        $this->assertSame(2, $updated->quantity);
        $this->assertSame('low_stock', $updated->status->value);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($updated->cover_image_path);
    }

    public function test_update_without_quantity_keeps_existing_values(): void
    {
        $stock = Stock::factory()->create(['quantity' => 2, 'min_stock' => 5, 'status' => 'available']);

        $updated = $this->service()->update($stock, ['description' => 'Baru']);

        $this->assertSame('Baru', $updated->description);
        $this->assertSame(2, $updated->quantity);
        $this->assertSame('low_stock', $updated->status->value);
    }

    public function test_adjust_quantity_syncs_status_and_never_goes_negative(): void
    {
        $stock = Stock::factory()->create(['quantity' => 6, 'min_stock' => 5, 'status' => 'available']);

        $stock = $this->service()->adjustQuantity($stock, -1);
        $this->assertSame(5, $stock->quantity);
        $this->assertSame('low_stock', $stock->status->value);

        $stock = $this->service()->adjustQuantity($stock, -100);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame('out_of_stock', $stock->status->value);

        $stock = $this->service()->adjustQuantity($stock, 20);
        $this->assertSame('available', $stock->status->value);
    }

    public function test_delete_removes_record_and_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $withCover = $this->service()->create($user, ['name' => 'A'], UploadedFile::fake()->image('c.jpg'));
        $plain = $this->service()->create($user, ['name' => 'B']);
        $path = $withCover->cover_image_path;

        $this->service()->delete($withCover);
        $this->service()->delete($plain);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_list_filters_by_owner_search_and_status(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->create(['name' => 'Beras', 'sku' => 'BR-1', 'status' => 'available']);
        Stock::factory()->for($user)->create(['name' => 'Gula', 'sku' => 'GL-1', 'status' => 'out_of_stock']);
        Stock::factory()->create(['name' => 'Beras Orang Lain']);

        $this->assertSame(2, $this->service()->list($user)->total());
        $this->assertSame(1, $this->service()->list($user, 'Beras')->total());
        $this->assertSame(1, $this->service()->list($user, 'GL-1')->total());
        $this->assertSame(1, $this->service()->list($user, null, 'out_of_stock')->total());
        $this->assertCount(2, $this->service()->allFor($user));
    }
}
