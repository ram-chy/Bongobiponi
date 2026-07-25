<?php

namespace App\Services;

use App\Mail\SendOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function sendOtp(string $email): void
    {
        DB::transaction(function () use ($email) {
            PasswordResetOtp::where('email', $email)
                ->whereNull('completed_at')
                ->where('expires_at', '>', now())
                ->update(['used_at' => now()]);

            // 6-digit OTP for 900,000 combinations (vs 10,000 for 4-digit)
            $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            PasswordResetOtp::create([
                'email' => $email,
                'otp' => $otp,
                'expires_at' => now()->addMinutes(10),
            ]);
        });

        try {
            Mail::to($email)->send(new SendOtpMail($otp));
        } catch (\Throwable) {
            // Silently ignore mail failures to prevent email enumeration
        }
    }

    private const MAX_OTP_ATTEMPTS = 5;

    public function verifyOtp(string $email, string $otp): ?string
    {
        $latestRecord = PasswordResetOtp::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$latestRecord) {
            return null;
        }

        if ($latestRecord->failed_attempts >= self::MAX_OTP_ATTEMPTS) {
            return null;
        }

        if (! hash_equals((string) $latestRecord->otp, (string) $otp)) {
            $latestRecord->increment('failed_attempts');
            return null;
        }

        $token = Str::random(60);

        $latestRecord->update([
            'token' => hash('sha256', $token),
            'used_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        return $token;
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        $hashedToken = hash('sha256', $token);

        $record = PasswordResetOtp::where('email', $email)
            ->where('token', $hashedToken)
            ->whereNotNull('used_at')
            ->whereNull('completed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return false;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        $user->update([
            'password' => $password,
            'token_version' => ($user->token_version ?? 0) + 1,
        ]);

        PasswordResetOtp::where('email', $email)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);

        return true;
    }
}
