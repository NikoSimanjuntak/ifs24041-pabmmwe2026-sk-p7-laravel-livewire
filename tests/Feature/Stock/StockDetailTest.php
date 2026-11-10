<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->member = User::factory()->create();
        $this->stock = Stock::factory()->for($this->member)->create([
            'name' => 'Beras', 'sku' => 'BR-1', 'description' => '# Judul', 'quantity' => 20,
            'unit' => 'kg', 'price' => 12000, 'min_stock' => 5, 'status' => 'available',
        ]);
    }

    private function component()
    {
        return Livewire::actingAs($this->member)->test(StockDetail::class, ['stock' => $this->stock]);
    }

    public function test_detail_page_renders_for_owner(): void
    {
        $this->actingAs($this->member)->get('/stocks/'.$this->stock->id)
            ->assertOk()->assertSeeLivewire(StockDetail::class)->assertSee('Beras')->assertSee('Rp 12.000');
    }

    public function test_detail_shows_placeholder_without_cover_and_cover_when_present(): void
    {
        $this->component()->assertSee('Belum ada cover');

        $this->stock->update(['cover_image_path' => 'stock-covers/a.jpg', 'price' => null, 'sku' => null]);
        $this->component()->assertSee('stock-covers/a.jpg')->assertDontSee('Belum ada cover');
    }

    public function test_other_users_cannot_open_detail(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(StockDetail::class, ['stock' => $this->stock])
            ->assertForbidden();
    }

    public function test_edit_flow_updates_stock(): void
    {
        $this->component()
            ->call('startEdit')
            ->assertSet('editing', true)
            ->set('name', 'Beras Super')
            ->set('quantity', 3)
            ->set('sku', '')
            ->set('price', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false)
            ->assertSee('Stok berhasil diperbarui.');

        $this->stock->refresh();
        $this->assertSame('Beras Super', $this->stock->name);
        $this->assertNull($this->stock->sku);
        $this->assertNull($this->stock->price);
        $this->assertSame('low_stock', $this->stock->status->value);
    }

    public function test_edit_validates_and_cancel_restores_values(): void
    {
        $this->component()
            ->call('startEdit')
            ->set('name', '')
            ->set('quantity', -5)
            ->call('save')
            ->assertHasErrors(['name', 'quantity'])
            ->set('name', 'Ubah')
            ->call('cancelEdit')
            ->assertSet('name', 'Beras')
            ->assertSet('editing', false)
            ->assertHasNoErrors();
    }

    public function test_adjust_quantity_buttons(): void
    {
        $this->component()->call('adjust', -10)->assertSet('quantity', 10);
        $this->assertSame(10, $this->stock->refresh()->quantity);
        $this->assertSame('available', $this->stock->status->value);

        $this->component()->call('adjust', -8);
        $this->assertSame('low_stock', $this->stock->refresh()->status->value);

        $this->component()->call('adjust', -100000);
        $this->assertSame('out_of_stock', $this->stock->refresh()->status->value);
        $this->assertSame(0, $this->stock->quantity);
    }

    public function test_save_description_markdown(): void
    {
        $this->component()
            ->set('description', "## Catatan\n- item **penting**")
            ->call('saveDescription')
            ->assertHasNoErrors()
            ->assertSee('Deskripsi berhasil disimpan.');

        $this->assertSame("## Catatan\n- item **penting**", $this->stock->refresh()->description);

        $this->component()->set('description', '')->call('saveDescription');
        $this->assertNull($this->stock->refresh()->description);
    }

    public function test_cover_upload_replaces_previous_cover(): void
    {
        $this->component()->set('cover', UploadedFile::fake()->image('a.jpg'))->call('saveCover')->assertHasNoErrors();
        $first = $this->stock->refresh()->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->component()->set('cover', UploadedFile::fake()->image('b.jpg'))->call('saveCover');
        $second = $this->stock->refresh()->cover_image_path;

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_cover_upload_validation(): void
    {
        $this->component()->call('saveCover')->assertHasErrors(['cover' => 'required']);
        $this->component()->set('cover', UploadedFile::fake()->create('doc.pdf', 10))->call('saveCover')->assertHasErrors('cover');
    }
}
