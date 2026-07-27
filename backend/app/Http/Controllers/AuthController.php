<?php

namespace App\Http\Controllers;

use App\Helpers\JWTHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role_id'] = Role::where('slug', 'regular_user')->value('id');

        $user = User::create($data);

        $token = JWTHelper::generateToken($user);

        return response()->json([
            'message' => 'User created successfully',
            'data' => [
                'user' => $user->load('role'),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTHelper::getTTL(),
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $token = JWTHelper::attemptLogin($credentials);

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $this->respondWithToken($token);
    }

    public function me(): JsonResponse
    {
        $user = JWTHelper::getUser();

        return response()->json([
            'data' => $user?->load('role'),
        ]);
    }

    public function logout(): JsonResponse
    {
        $invalidated = JWTHelper::invalidateToken();

        $cookie = cookie()->forget(self::COOKIE_NAME);

        $response = response()->json([
            'message' => 'Successfully logged out',
        ])->withCookie($cookie);

        if (! $invalidated) {
            report(new \Exception('Failed to blacklist JWT during logout'));
        }

        return $response;
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(JWTHelper::refreshToken());
    }

    private const COOKIE_NAME = 'auth_token';

    private const COOKIE_SECONDS = 3600; // 60 minutes

    private function respondWithToken(string $token): JsonResponse
    {
        $user = auth('api')->user();

        $cookie = cookie(
            self::COOKIE_NAME,
            $token,
            self::COOKIE_SECONDS / 60,
            '/',
            config('session.domain') ?: null,
            config('app.env') === 'production',
            false, // Must be false so frontend JS can read it for hydration after page refresh
            false,
            'Lax'
        );

        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTHelper::getTTL(),
                'user' => $user?->load('role'),
            ],
        ])->withCookie($cookie);
    }
}
