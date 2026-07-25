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
            if (! Schema::hasColumn('users', 'token_version')) {
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
            // If token parsing fails, let the auth:api middleware handle it
        }

        return $next($request);
    }
}
