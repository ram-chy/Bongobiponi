<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No Account Found for this email',
            ], 404);
        }

        try {
            $this->passwordResetService->sendOtp($request->email);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 429);
        }

        return response()->json([
            'message' => 'OTP sent to your email',
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No Account Found for this email',
            ], 404);
        }

        $token = $this->passwordResetService->verifyOtp(
            $request->email,
            $request->otp,
        );

        if (!$token) {
            return response()->json([
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        return response()->json([
            'message' => 'OTP verified successfully',
            'data' => [
                'reset_token' => $token,
            ],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json([
                'message' => 'The token field is required.',
            ], 422);
        }

        $reset = $this->passwordResetService->resetPassword(
            $request->email,
            $token,
            $request->password,
        );

        if (!$reset) {
            return response()->json([
                'message' => 'Invalid token or email',
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }
}
