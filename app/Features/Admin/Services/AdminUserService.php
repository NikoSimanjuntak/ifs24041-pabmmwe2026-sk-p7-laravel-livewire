<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminUserService
{
    public function __construct(private readonly StockRepositoryInterface $stocks) {}

    public function paginateUsers(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount('stocks')
            ->withSum('stocks', 'quantity')
            ->when($search, function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('username')
            ->paginate($perPage);
    }

    public function paginateStocksOf(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stocks->paginateForUser($user->id, $search, $status, $perPage);
    }
}
