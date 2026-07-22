<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegularUserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user?->hasRole('regular_user')) {
            return response()->json(['message' => 'Forbidden: Regular users only'], 403);
        }

        if ($request->isMethod('get') && $request->route('customer')) {
            // handled by service layer filtering
        }

        return $next($request);
    }
}
