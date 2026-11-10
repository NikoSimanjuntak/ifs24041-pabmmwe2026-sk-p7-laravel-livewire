<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Stok Barang')]
class StockList extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public $quantity = 0;

    public string $unit = 'pcs';

    public $price = null;

    public $min_stock = 5;

    public $cover = null;

    public bool $showImport = false;

    public $importFile = null;

    public bool $showAi = false;

    public string $aiPrompt = '';

    public int $aiCount = 5;

    public array $aiItems = [];

    public array $aiSelected = [];

    public ?string $aiError = null;

    public ?string $message = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Stock::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $stock = $this->ownedStock($id);

        $this->resetForm();
        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = (string) $stock->sku;
        $this->description = (string) $stock->description;
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price;
        $this->min_stock = $stock->min_stock;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(StockService $service): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:65000'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'cover' => ['nullable', 'image', 'max:2048'],
        ]);

        $cover = $data['cover'] ?? null;
        unset($data['cover']);

        $data['sku'] = filled($data['sku']) ? $data['sku'] : null;
        $data['description'] = filled($data['description']) ? $data['description'] : null;
        $data['price'] = filled($data['price']) ? $data['price'] : null;

        if ($this->editingId !== null) {
            $service->update($this->ownedStock($this->editingId), $data, $cover);
            $this->message = 'Stok berhasil diperbarui.';
        } else {
            $this->authorize('create', Stock::class);
            $service->create(auth()->user(), $data, $cover);
            $this->message = 'Stok berhasil ditambahkan.';
        }

        $this->closeForm();
    }

    public function delete(int $id, StockService $service): void
    {
        $service->delete($this->ownedStock($id, 'delete'));
        $this->message = 'Stok berhasil dihapus.';
    }

    public function adjust(int $id, int $delta, StockService $service): void
    {
        $stock = $this->ownedStock($id);
        $service->adjustQuantity($stock, max(-1000, min(1000, $delta)));
    }

    public function export(StockService $service, StockExcelService $excel): StreamedResponse
    {
        $content = $excel->export($service->allFor(auth()->user()));

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'stocks.xlsx');
    }

    public function import(StockExcelService $excel): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'extensions:xlsx,xls', 'max:5120'],
        ]);

        try {
            $count = $excel->import(auth()->user(), $this->importFile->getRealPath());
            $this->message = "{$count} stok berhasil diimpor.";
            $this->showImport = false;
        } catch (Throwable $e) {
            $this->addError('importFile', 'Gagal mengimpor: '.$e->getMessage());
        }

        $this->reset('importFile');
    }

    public function generateAi(AiClientInterface $ai): void
    {
        $this->validate([
            'aiPrompt' => ['required', 'string', 'min:3', 'max:500'],
            'aiCount' => ['required', 'integer', 'between:1,10'],
        ]);

        $this->aiError = null;

        try {
            $items = $ai->generate($this->aiPrompt, $this->aiCount);
            $this->aiItems = array_map(fn ($item) => $item->toArray(), $items);
            $this->aiSelected = array_keys($this->aiItems);
        } catch (Throwable $e) {
            $this->aiItems = [];
            $this->aiSelected = [];
            $this->aiError = 'Gagal membuat item dengan AI: '.$e->getMessage();
        }
    }

    public function saveAi(StockService $service): void
    {
        $this->validate([
            'aiSelected' => ['required', 'array', 'min:1'],
        ], ['aiSelected.required' => 'Pilih minimal satu item.', 'aiSelected.min' => 'Pilih minimal satu item.']);

        $this->authorize('create', Stock::class);

        $saved = 0;
        foreach ($this->aiSelected as $index) {
            $item = $this->aiItems[(int) $index] ?? null;

            if ($item === null) {
                continue;
            }

            $service->create(auth()->user(), $item);
            $saved++;
        }

        $this->message = "{$saved} item AI berhasil disimpan.";
        $this->reset(['aiItems', 'aiSelected', 'aiPrompt', 'showAi']);
    }

    public function render(StockService $service): View
    {
        return view('livewire.stock.stock-list', [
            'stocks' => $service->list(auth()->user(), $this->search, $this->statusFilter),
            'statuses' => StockStatus::cases(),
        ]);
    }

    private function ownedStock(int $id, string $ability = 'update'): Stock
    {
        $stock = Stock::findOrFail($id);
        $this->authorize($ability, $stock);

        return $stock;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock', 'cover']);
        $this->resetValidation();
    }
}
