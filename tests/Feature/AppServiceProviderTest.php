<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Features\Stock\Repositories\StockRepository;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_container_bindings(): void
    {
        $this->assertInstanceOf(StockRepository::class, $this->app->make(StockRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, $this->app->make(AiClientInterface::class));
    }

    public function test_stock_policy_is_registered(): void
    {
        $this->assertInstanceOf(StockPolicy::class, Gate::getPolicyFor(Stock::class));
    }

    public function test_rate_limiter_allows_1000_per_second(): void
    {
        $limiter = RateLimiter::limiter('global');
        $limit = $limiter(Request::create('/'));

        $this->assertSame(1000, $limit->maxAttempts);
        $this->assertSame(1, $limit->decaySeconds);
    }

    public function test_https_is_not_forced_by_default(): void
    {
        $this->assertStringStartsWith('http://', url('/x'));
    }

    public function test_https_is_forced_when_enabled(): void
    {
        config(['security.force_https' => true]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/x'));
    }
}
