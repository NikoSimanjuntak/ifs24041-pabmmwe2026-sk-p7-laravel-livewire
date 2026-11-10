<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generate(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY belum diatur.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('openai.timeout', 30))
            ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                'model' => config('openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt($count)],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Permintaan ke OpenAI gagal (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $items = is_array($decoded) ? ($decoded['items'] ?? null) : null;

        if (! is_array($items)) {
            throw new RuntimeException('Respons AI tidak valid.');
        }

        $result = [];
        foreach ($items as $item) {
            if (! is_array($item) || blank($item['name'] ?? null)) {
                continue;
            }

            $result[] = GeneratedStockItem::fromArray($item);

            if (count($result) >= $count) {
                break;
            }
        }

        return $result;
    }

    private function systemPrompt(int $count): string
    {
        return 'Kamu adalah asisten inventaris. Berdasarkan permintaan pengguna, buat daftar maksimal '.$count
            .' item stok barang. Balas HANYA dengan JSON berbentuk {"items":[{"name":string,"sku":string,'
            .'"description":string (Markdown singkat),"quantity":integer,"unit":string,"price":number,'
            .'"min_stock":integer}]}. Gunakan bahasa Indonesia dan harga dalam Rupiah.';
    }
}
