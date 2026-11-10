<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Stok')]
class StockDetail extends Component
{
    use WithFileUploads;

    public Stock $stock;

    public bool $editing = false;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public $quantity = 0;

    public string $unit = 'pcs';

    public $price = null;

    public $min_stock = 5;

    public $cover = null;

    public ?string $message = null;

    public function mount(Stock $stock): void
    {
        $this->authorize('view', $stock);
        $this->stock = $stock;
        $this->description = (string) $stock->description;
        $this->fillFromStock();
    }

    public function startEdit(): void
    {
        $this->authorize('update', $this->stock);
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->fillFromStock();
        $this->resetValidation();
        $this->editing = false;
    }

    public function save(StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ]);

        $data['sku'] = filled($data['sku']) ? $data['sku'] : null;
        $data['price'] = filled($data['price']) ? $data['price'] : null;

        $this->stock = $service->update($this->stock, $data);
        $this->fillFromStock();
        $this->editing = false;
        $this->message = 'Stok berhasil diperbarui.';
    }

    public function saveDescription(StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $data = $this->validate([
            'description' => ['nullable', 'string', 'max:65000'],
        ]);

        $this->stock = $service->update($this->stock, [
            'description' => filled($data['description']) ? $data['description'] : null,
        ]);
        $this->message = 'Deskripsi berhasil disimpan.';
    }

    public function saveCover(StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $data = $this->validate([
            'cover' => ['required', 'image', 'max:2048'],
        ]);

        $this->stock = $service->update($this->stock, [], $data['cover']);
        $this->reset('cover');
        $this->message = 'Cover berhasil diperbarui.';
    }

    public function adjust(int $delta, StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $this->stock = $service->adjustQuantity($this->stock, max(-1000, min(1000, $delta)));
        $this->fillFromStock();
    }

    public function render(): View
    {
        return view('livewire.stock.stock-detail');
    }

    private function fillFromStock(): void
    {
        $this->name = $this->stock->name;
        $this->sku = (string) $this->stock->sku;
        $this->quantity = $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price;
        $this->min_stock = $this->stock->min_stock;
    }
}
