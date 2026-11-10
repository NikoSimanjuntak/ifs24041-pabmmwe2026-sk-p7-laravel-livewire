<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockExcelService
{
    public const HEADERS = ['name', 'sku', 'qty', 'unit', 'price', 'min_stock', 'status', 'description'];

    public function __construct(private readonly StockService $stocks) {}

    /** @param Collection<int, Stock> $stocks */
    public function export(Collection $stocks): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stocks');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $rowNumber = 2;
        foreach ($stocks as $stock) {
            $sheet->fromArray([
                $stock->name,
                $stock->sku,
                $stock->quantity,
                $stock->unit,
                $stock->price,
                $stock->min_stock,
                $stock->status->value,
                $stock->description,
            ], null, 'A'.$rowNumber);
            $rowNumber++;
        }

        $path = tempnam(sys_get_temp_dir(), 'stk');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    public function import(User $user, string $path): int
    {
        $rows = IOFactory::load($path, 0, [IOFactory::READER_XLSX, IOFactory::READER_XLS])
            ->getActiveSheet()
            ->toArray(null, true, true, false);

        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), array_shift($rows));

        if (! in_array('name', $headers, true)) {
            throw new InvalidArgumentException('Kolom "name" tidak ditemukan pada baris pertama.');
        }

        $count = 0;
        foreach ($rows as $row) {
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = $row[$index] ?? null;
            }

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $sku = trim((string) ($data['sku'] ?? ''));
            $description = trim((string) ($data['description'] ?? ''));
            $unit = trim((string) ($data['unit'] ?? ''));
            $quantity = $data['qty'] ?? $data['quantity'] ?? 0;
            $price = $data['price'] ?? null;
            $minStock = $data['min_stock'] ?? null;

            $this->stocks->create($user, [
                'name' => $name,
                'sku' => $sku !== '' ? $sku : null,
                'description' => $description !== '' ? $description : null,
                'unit' => $unit !== '' ? $unit : 'pcs',
                'quantity' => is_numeric($quantity) ? max(0, (int) $quantity) : 0,
                'price' => is_numeric($price) ? (float) $price : null,
                'min_stock' => is_numeric($minStock) ? max(0, (int) $minStock) : 5,
            ]);
            $count++;
        }

        return $count;
    }
}
