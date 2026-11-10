<?php

namespace Tests\Unit;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_from_array_normalizes_values(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => '  Pulpen ',
            'sku' => ' PLP-1 ',
            'description' => ' **biru** ',
            'quantity' => -3,
            'unit' => 'lusin',
            'price' => '15000',
            'min_stock' => 2,
        ]);

        $this->assertSame([
            'name' => 'Pulpen',
            'sku' => 'PLP-1',
            'description' => '**biru**',
            'quantity' => 0,
            'unit' => 'lusin',
            'price' => 15000.0,
            'min_stock' => 2,
        ], $item->toArray());
    }

    public function test_from_array_applies_defaults(): void
    {
        $item = GeneratedStockItem::fromArray(['name' => 'Pensil', 'price' => 'abc']);

        $this->assertSame([
            'name' => 'Pensil',
            'sku' => null,
            'description' => null,
            'quantity' => 0,
            'unit' => 'pcs',
            'price' => null,
            'min_stock' => 5,
        ], $item->toArray());
    }
}
