<?php

namespace Tests\Feature\Http;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_open_own_stock_but_not_others(): void
    {
        $member = User::factory()->create();
        $own = Stock::factory()->for($member)->create();
        $other = Stock::factory()->create();

        $this->actingAs($member)->get('/stocks/'.$own->id)->assertOk();
        $this->actingAs($member)->get('/stocks/'.$other->id)->assertForbidden();
    }

    public function test_missing_stock_returns_404(): void
    {
        $this->actingAs(User::factory()->create())->get('/stocks/999999')->assertNotFound();
    }

    public function test_ownership_middleware_resolves_raw_id_parameter(): void
    {
        $member = User::factory()->create();
        $own = Stock::factory()->for($member)->create();

        $response = (new EnsureStockOwnership)->handle($this->requestWithStockParam($member, (string) $own->id), fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_ownership_middleware_rejects_raw_id_of_other_user(): void
    {
        $member = User::factory()->create();
        $other = Stock::factory()->create();

        $this->expectException(HttpException::class);

        (new EnsureStockOwnership)->handle($this->requestWithStockParam($member, (string) $other->id), fn () => response('ok'));
    }

    private function requestWithStockParam(User $user, string $id): Request
    {
        $request = Request::create('/stocks/'.$id);
        $request->setUserResolver(fn () => $user);

        $route = new Route('GET', '/stocks/{stock}', fn () => null);
        $route->bind($request);
        $route->setParameter('stock', $id);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
