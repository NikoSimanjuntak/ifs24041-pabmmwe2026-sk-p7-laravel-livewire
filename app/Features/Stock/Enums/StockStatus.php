<?php

namespace App\Features\Stock\Enums;

enum StockStatus: string
{
    case Available = 'available';
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::LowStock => 'Stok Menipis',
            self::OutOfStock => 'Habis',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'bg-success',
            self::LowStock => 'bg-warning text-dark',
            self::OutOfStock => 'bg-danger',
        };
    }

    public static function fromQuantity(int $quantity, int $minStock): self
    {
        if ($quantity <= 0) {
            return self::OutOfStock;
        }

        if ($quantity <= $minStock) {
            return self::LowStock;
        }

        return self::Available;
    }
}
