<?php

namespace Tests\Feature\Ai;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    private function fakeReply(string $content, int $status = 200): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status),
        ]);
    }

    public function test_requires_api_key(): void
    {
        config(['openai.api_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        (new OpenAiStockGeneratorService)->generate('alat tulis');
    }

    public function test_generates_items_skips_invalid_and_respects_count(): void
    {
        config(['openai.api_key' => 'test-key']);
        $this->fakeReply(json_encode(['items' => [
            ['name' => 'Pulpen', 'sku' => 'PLP-1', 'description' => 'Biru', 'quantity' => 10, 'unit' => 'lusin', 'price' => 15000, 'min_stock' => 2],
            'bukan-array',
            ['name' => ''],
            ['name' => 'Pensil'],
            ['name' => 'Penggaris'],
        ]]));

        $items = (new OpenAiStockGeneratorService)->generate('alat tulis', 2);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(GeneratedStockItem::class, $items);
        $this->assertSame('Pulpen', $items[0]->name);
        $this->assertSame('Pensil', $items[1]->name);

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === config('openai.model')
                && str_contains($request['messages'][1]['content'], 'alat tulis');
        });
    }

    public function test_throws_on_http_failure(): void
    {
        config(['openai.api_key' => 'test-key']);
        $this->fakeReply('{}', 500);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_throws_on_invalid_payload(): void
    {
        config(['openai.api_key' => 'test-key']);
        $this->fakeReply('bukan json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak valid');

        (new OpenAiStockGeneratorService)->generate('x');
    }
}
