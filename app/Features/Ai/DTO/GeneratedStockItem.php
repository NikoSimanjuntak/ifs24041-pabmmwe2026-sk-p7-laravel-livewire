<?php

namespace App\Features\Ai\DTO;

final readonly class GeneratedStockItem
{
    public function __construct(
        public string $name,
        public ?string $sku,
        public ?string $description,
        public int $quantity,
        public string $unit,
        public ?float $price,
        public int $minStock,
    ) {}

    public static function fromArray(array $data): self
    {
        $sku = trim((string) ($data['sku'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $unit = trim((string) ($data['unit'] ?? ''));
        $price = $data['price'] ?? null;

        return new self(
            name: trim((string) $data['name']),
            sku: $sku !== '' ? $sku : null,
            description: $description !== '' ? $description : null,
            quantity: max(0, (int) ($data['quantity'] ?? 0)),
            unit: $unit !== '' ? $unit : 'pcs',
            price: is_numeric($price) ? (float) $price : null,
            minStock: max(0, (int) ($data['min_stock'] ?? 5)),
        );
    }

    /** @return array{name: string, sku: ?string, description: ?string, quantity: int, unit: string, price: ?float, min_stock: int} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
        ];
    }
}
