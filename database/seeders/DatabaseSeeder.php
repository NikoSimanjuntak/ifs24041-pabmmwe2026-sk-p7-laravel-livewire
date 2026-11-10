<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'password' => 'admin', 'role' => User::ROLE_ADMIN],
        );

        $member = User::updateOrCreate(
            ['username' => 'member'],
            ['name' => 'Member Demo', 'password' => 'member', 'role' => User::ROLE_MEMBER],
        );

        $samples = [
            ['Beras Premium 5kg', 'BRS-001', 40, 'karung', 68000, 10],
            ['Minyak Goreng 1L', 'MYK-001', 4, 'botol', 17500, 5],
            ['Gula Pasir 1kg', 'GLA-001', 0, 'kg', 16000, 5],
            ['Kopi Bubuk 200g', 'KPI-001', 25, 'pak', 22000, 5],
        ];

        foreach ($samples as [$name, $sku, $quantity, $unit, $price, $min]) {
            Stock::updateOrCreate(
                ['user_id' => $member->id, 'sku' => $sku],
                [
                    'name' => $name,
                    'description' => "Contoh stok **{$name}**.",
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'price' => $price,
                    'min_stock' => $min,
                    'status' => StockStatus::fromQuantity($quantity, $min)->value,
                ],
            );
        }
    }
}
