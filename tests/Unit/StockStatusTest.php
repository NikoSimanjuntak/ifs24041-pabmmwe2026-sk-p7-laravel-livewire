<?php

namespace Tests\Unit;

use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\TestCase;

class StockStatusTest extends TestCase
{
    public function test_from_quantity_returns_out_of_stock_when_zero(): void
    {
        $this->assertSame(StockStatus::OutOfStock, StockStatus::fromQuantity(0, 5));
        $this->assertSame(StockStatus::OutOfStock, StockStatus::fromQuantity(-1, 5));
        $this->assertSame(StockStatus::OutOfStock, StockStatus::fromQuantity(0, 0));
    }

    public function test_from_quantity_returns_low_stock_at_or_below_minimum(): void
    {
        $this->assertSame(StockStatus::LowStock, StockStatus::fromQuantity(1, 5));
        $this->assertSame(StockStatus::LowStock, StockStatus::fromQuantity(5, 5));
    }

    public function test_from_quantity_returns_available_above_minimum(): void
    {
        $this->assertSame(StockStatus::Available, StockStatus::fromQuantity(6, 5));
    }

    public function test_labels(): void
    {
        $this->assertSame('Tersedia', StockStatus::Available->label());
        $this->assertSame('Stok Menipis', StockStatus::LowStock->label());
        $this->assertSame('Habis', StockStatus::OutOfStock->label());
    }

    public function test_badge_classes(): void
    {
        $this->assertSame('bg-success', StockStatus::Available->badgeClass());
        $this->assertSame('bg-warning text-dark', StockStatus::LowStock->badgeClass());
        $this->assertSame('bg-danger', StockStatus::OutOfStock->badgeClass());
    }
}
