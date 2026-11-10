<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    public function list(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user->id, $search, $status, $perPage);
    }

    /** @return Collection<int, Stock> */
    public function allFor(User $user): Collection
    {
        return $this->repository->allForUser($user->id);
    }

    public function create(User $user, array $data, ?UploadedFile $cover = null): Stock
    {
        $data['quantity'] = (int) ($data['quantity'] ?? 0);
        $data['min_stock'] = (int) ($data['min_stock'] ?? 5);
        $data['unit'] = $data['unit'] ?? 'pcs';
        $data['user_id'] = $user->id;
        $data['status'] = StockStatus::fromQuantity($data['quantity'], $data['min_stock'])->value;

        if ($cover !== null) {
            $data['cover_image_path'] = $cover->store('stock-covers', 'public');
        }

        return $this->repository->create($data);
    }

    public function update(Stock $stock, array $data, ?UploadedFile $cover = null): Stock
    {
        $quantity = (int) ($data['quantity'] ?? $stock->quantity);
        $minStock = (int) ($data['min_stock'] ?? $stock->min_stock);

        if (array_key_exists('quantity', $data)) {
            $data['quantity'] = $quantity;
        }

        if (array_key_exists('min_stock', $data)) {
            $data['min_stock'] = $minStock;
        }

        $data['status'] = StockStatus::fromQuantity($quantity, $minStock)->value;

        if ($cover !== null) {
            $this->deleteCover($stock);
            $data['cover_image_path'] = $cover->store('stock-covers', 'public');
        }

        return $this->repository->update($stock, $data);
    }

    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $quantity = max(0, $stock->quantity + $delta);

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock)->value,
        ]);
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCover($stock);
        $this->repository->delete($stock);
    }

    private function deleteCover(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }
}
