<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->when($search, function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function allForUser(int $userId): Collection
    {
        return Stock::query()->where('user_id', $userId)->orderBy('name')->get();
    }

    public function create(array $data): Stock
    {
        return Stock::create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update($data);

        return $stock->refresh();
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
