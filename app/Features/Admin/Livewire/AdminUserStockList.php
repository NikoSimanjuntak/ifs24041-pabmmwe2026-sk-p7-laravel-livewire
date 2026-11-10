<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Stok User')]
class AdminUserStockList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public User $user;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(AdminUserService $service): View
    {
        return view('livewire.admin.admin-user-stock-list', [
            'stocks' => $service->paginateStocksOf($this->user, $this->search, $this->statusFilter),
            'statuses' => StockStatus::cases(),
        ]);
    }
}
