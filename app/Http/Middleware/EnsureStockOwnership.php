<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $stock = $request->route('stock');

        if ($stock !== null) {
            $stock = $stock instanceof Stock ? $stock : Stock::findOrFail($stock);

            abort_unless($stock->user_id === $request->user()?->id, 403);
        }

        return $next($request);
    }
}
