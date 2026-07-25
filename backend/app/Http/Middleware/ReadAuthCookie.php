<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadAuthCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        // If no Authorization header, read token from HttpOnly cookie and set header
        // This allows the auth:api middleware to authenticate via cookie
        if (! $request->bearerToken()) {
            $token = $request->cookie('auth_token');

            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}