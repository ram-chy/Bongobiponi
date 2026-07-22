<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTHelper
{
    public static function generateToken(User $user): string
    {
        return JWTAuth::claims([
            'email' => $user->email,
        ])->fromUser($user);
    }

    public static function attemptLogin(array $credentials): ?string
    {
        $token = Auth::guard('api')->attempt($credentials);

        return $token ?: null;
    }

    public static function getUser(): ?User
    {
        try {
            $user = Auth::guard('api')->user();
        } catch (JWTException) {
            return null;
        }

        return $user;
    }

    public static function decodeToken(?string $token = null): array
    {
        try {
            if ($token) {
                JWTAuth::setToken($token);
            }

            $payload = JWTAuth::getPayload();

            return $payload->toArray();
        } catch (JWTException) {
            return [];
        }
    }

    public static function refreshToken(): string
    {
        return Auth::guard('api')->refresh();
    }

    public static function invalidateToken(): bool
    {
        try {
            Auth::guard('api')->logout();

            return true;
        } catch (JWTException) {
            return false;
        }
    }

    public static function getTTL(): int
    {
        return Auth::guard('api')->factory()->getTTL() * 60;
    }

    public static function getTokenFromRequest(): ?string
    {
        try {
            return JWTAuth::parseToken()->getToken()?->get();
        } catch (JWTException) {
            return null;
        }
    }
}
