<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tymon\JWTAuth\Facades\JWTAuth;

class ValidateTokenVersion
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $next($request);
            }

            // Skip validation if token_version column doesn't exist yet (pre-migration)
            static $hasColumn = null;
            if ($hasColumn === null) {
                $hasColumn = Schema::hasColumn('users', 'token_version');
            }
            if (! $hasColumn) {
                return $next($request);
            }

            $payload = JWTAuth::parseToken()->getPayload();
            $tokenVersion = $payload->get('token_version');

            // Skip validation for old tokens that don't have the claim
            if ($tokenVersion === null) {
                return $next($request);
            }

            if ((int) $tokenVersion !== (int) $user->token_version) {
                return response()->json(['message' => 'Token has been invalidated. Please log in again.'], 401);
            }
        } catch (\Exception) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        return $next($request);
    }
}
