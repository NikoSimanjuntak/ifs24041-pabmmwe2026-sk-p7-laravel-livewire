<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\Support\MakesSpreadsheets;
use Tests\TestCase;

class StockListTest extends TestCase
{
    use MakesSpreadsheets;
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->member = User::factory()->create();
    }

    private function component()
    {
        return Livewire::actingAs($this->member)->test(StockList::class);
    }

    public function test_page_renders_for_member(): void
    {
        $this->actingAs($this->member)->get('/stocks')->assertOk()->assertSeeLivewire(StockList::class);
    }

    public function test_lists_only_own_stocks(): void
    {
        Stock::factory()->for($this->member)->create(['name' => 'Milik Saya']);
        Stock::factory()->create(['name' => 'Milik Orang']);

        $this->component()->assertSee('Milik Saya')->assertDontSee('Milik Orang');
    }

    public function test_shows_cover_image_and_empty_state(): void
    {
        $this->component()->assertSee('Belum ada stok.');

        Stock::factory()->for($this->member)->create(['cover_image_path' => 'stock-covers/x.jpg']);
        $this->component()->assertSee('stock-covers/x.jpg');
    }

    public function test_search_and_status_filter(): void
    {
        Stock::factory()->for($this->member)->create(['name' => 'Beras', 'sku' => 'BR-9', 'status' => 'available']);
        Stock::factory()->for($this->member)->create(['name' => 'Gula', 'sku' => 'GL-9', 'quantity' => 0, 'status' => 'out_of_stock']);

        $this->component()
            ->set('search', 'Beras')->assertSee('Beras')->assertDontSee('Gula')
            ->set('search', 'GL-9')->assertSee('Gula')->assertDontSee('Beras')
            ->set('search', '')
            ->set('statusFilter', 'out_of_stock')->assertSee('Gula')->assertDontSee('Beras');
    }

    public function test_create_stock_with_cover(): void
    {
        $this->component()
            ->call('create')
            ->assertSet('showForm', true)
            ->set('name', 'Beras')
            ->set('sku', 'BR-1')
            ->set('description', '**premium**')
            ->set('quantity', 10)
            ->set('unit', 'kg')
            ->set('price', '12000')
            ->set('min_stock', 3)
            ->set('cover', UploadedFile::fake()->image('c.jpg'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSee('Stok berhasil ditambahkan.');

        $stock = Stock::where('name', 'Beras')->firstOrFail();
        $this->assertSame($this->member->id, $stock->user_id);
        $this->assertSame('available', $stock->status->value);
        $this->assertSame('12000.00', $stock->price);
        Storage::disk('public')->assertExists($stock->cover_image_path);
    }

    public function test_blank_optional_fields_are_stored_as_null(): void
    {
        $this->component()
            ->call('create')
            ->set('name', 'Polos')
            ->set('sku', '')
            ->set('description', '')
            ->set('price', '')
            ->set('quantity', 0)
            ->set('min_stock', 5)
            ->call('save')
            ->assertHasNoErrors();

        $stock = Stock::where('name', 'Polos')->firstOrFail();
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertNull($stock->price);
        $this->assertSame('out_of_stock', $stock->status->value);
    }

    public function test_create_validates_input(): void
    {
        $this->component()
            ->call('create')
            ->set('name', '')
            ->set('quantity', -1)
            ->set('min_stock', 'abc')
            ->set('price', 'mahal')
            ->set('unit', '')
            ->call('save')
            ->assertHasErrors(['name', 'quantity', 'min_stock', 'price', 'unit']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_admin_cannot_create_stock(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(StockList::class)
            ->call('create')
            ->assertForbidden();
    }

    public function test_edit_updates_stock_and_status(): void
    {
        $stock = Stock::factory()->for($this->member)->create([
            'name' => 'Lama', 'sku' => 'S-1', 'description' => 'Desc', 'quantity' => 50, 'price' => 1000, 'min_stock' => 5, 'status' => 'available',
        ]);

        $this->component()
            ->call('edit', $stock->id)
            ->assertSet('editingId', $stock->id)
            ->assertSet('name', 'Lama')
            ->assertSet('sku', 'S-1')
            ->assertSet('showForm', true)
            ->set('name', 'Baru')
            ->set('quantity', 2)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Stok berhasil diperbarui.');

        $stock->refresh();
        $this->assertSame('Baru', $stock->name);
        $this->assertSame(2, $stock->quantity);
        $this->assertSame('low_stock', $stock->status->value);
    }

    public function test_cannot_edit_adjust_or_delete_other_users_stock(): void
    {
        $other = Stock::factory()->create();

        $this->component()->call('edit', $other->id)->assertForbidden();
        $this->component()->call('adjust', $other->id, 1)->assertForbidden();
        $this->component()->call('delete', $other->id)->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $other->id, 'quantity' => $other->quantity]);
    }

    public function test_close_form_resets_state(): void
    {
        $this->component()
            ->call('create')
            ->set('name', 'Sementara')
            ->call('closeForm')
            ->assertSet('showForm', false)
            ->assertSet('name', '');
    }

    public function test_delete_removes_stock(): void
    {
        $stock = Stock::factory()->for($this->member)->create();

        $this->component()->call('delete', $stock->id)->assertSee('Stok berhasil dihapus.');

        $this->assertModelMissing($stock);
    }

    public function test_adjust_quantity_syncs_status_and_clamps_delta(): void
    {
        $stock = Stock::factory()->for($this->member)->create(['quantity' => 6, 'min_stock' => 5, 'status' => 'available']);

        $this->component()->call('adjust', $stock->id, -1);
        $this->assertSame('low_stock', $stock->refresh()->status->value);
        $this->assertSame(5, $stock->quantity);

        $this->component()->call('adjust', $stock->id, -5);
        $this->assertSame('out_of_stock', $stock->refresh()->status->value);

        $this->component()->call('adjust', $stock->id, 999999);
        $this->assertSame(1000, $stock->refresh()->quantity);
        $this->assertSame('available', $stock->status->value);
    }

    public function test_export_downloads_xlsx(): void
    {
        Stock::factory()->for($this->member)->create(['name' => 'Beras']);

        $this->component()->call('export')->assertFileDownloaded('stocks.xlsx');
    }

    public function test_toggle_panels(): void
    {
        $this->component()
            ->call('$toggle', 'showImport')
            ->assertSet('showImport', true)
            ->assertSee('Import Excel')
            ->call('$toggle', 'showAi')
            ->assertSet('showAi', true)
            ->assertSee('Generate Item Stok dengan AI');
    }

    public function test_import_creates_stocks(): void
    {
        $content = $this->makeXlsx([
            ['name', 'sku', 'qty', 'unit', 'price', 'min_stock', 'status', 'description'],
            ['Beras', 'BR-1', 10, 'kg', 12000, 3, 'available', 'Premium'],
            ['Gula', 'GL-1', 0, 'kg', 15000, 5, 'out_of_stock', null],
        ]);

        $this->component()
            ->set('showImport', true)
            ->set('importFile', UploadedFile::fake()->createWithContent('stok.xlsx', $content))
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('showImport', false)
            ->assertSee('2 stok berhasil diimpor.');

        $this->assertSame(2, Stock::where('user_id', $this->member->id)->count());
    }

    public function test_import_requires_a_file(): void
    {
        $this->component()->call('import')->assertHasErrors(['importFile' => 'required']);
    }

    public function test_import_reports_invalid_file(): void
    {
        $this->component()
            ->set('showImport', true)
            ->set('importFile', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan excel'))
            ->call('import')
            ->assertHasErrors('importFile');

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_ai_generate_preview_and_save_selected(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willReturn([
            new GeneratedStockItem('Pulpen', 'PLP-1', 'Biru', 10, 'lusin', 15000.0, 2),
            new GeneratedStockItem('Pensil', null, null, 0, 'pcs', null, 5),
        ]);
        $this->app->instance(AiClientInterface::class, $stub);

        $component = $this->component()
            ->set('showAi', true)
            ->set('aiPrompt', 'alat tulis kantor')
            ->set('aiCount', 2)
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSee('Pulpen')
            ->assertSee('Pensil')
            ->assertSet('aiSelected', [0, 1]);

        $component->set('aiSelected', [0, 7])->call('saveAi')
            ->assertSee('1 item AI berhasil disimpan.')
            ->assertSet('aiItems', []);

        $this->assertDatabaseHas('stocks', ['name' => 'Pulpen', 'user_id' => $this->member->id, 'unit' => 'lusin']);
        $this->assertDatabaseMissing('stocks', ['name' => 'Pensil']);
    }

    public function test_ai_generate_validates_prompt(): void
    {
        $this->component()->set('aiPrompt', '')->call('generateAi')->assertHasErrors(['aiPrompt']);
        $this->component()->set('aiPrompt', 'valid prompt')->set('aiCount', 99)->call('generateAi')->assertHasErrors(['aiCount']);
    }

    public function test_ai_failure_is_shown_to_user(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willThrowException(new RuntimeException('layanan down'));
        $this->app->instance(AiClientInterface::class, $stub);

        $this->component()
            ->set('showAi', true)
            ->set('aiPrompt', 'alat tulis')
            ->call('generateAi')
            ->assertSet('aiItems', [])
            ->assertSee('Gagal membuat item dengan AI: layanan down');
    }

    public function test_save_ai_requires_a_selection(): void
    {
        $this->component()->set('aiSelected', [])->call('saveAi')->assertHasErrors('aiSelected');
    }
}
