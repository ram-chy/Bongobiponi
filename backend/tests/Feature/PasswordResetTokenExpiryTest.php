<?php

namespace Tests\Feature;

use App\Mail\SendOtpMail;
use App\Models\User;
use App\Services\PasswordResetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    private PasswordResetService $service;
    private string $email;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->service = new PasswordResetService();
        $user = User::factory()->create(['email' => 'reset@test.com']);
        $this->email = $user->email;
    }

    public function test_reset_token_works_within_15_minutes(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        $token = $this->service->verifyOtp($this->email, $otp);
        $this->assertNotNull($token);

        Carbon::setTestNow(now()->addMinutes(14));

        $result = $this->service->resetPassword($this->email, $token, 'NewPassword123!');
        $this->assertTrue($result);
    }

    public function test_reset_token_rejected_after_15_minutes(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        $token = $this->service->verifyOtp($this->email, $otp);
        $this->assertNotNull($token);

        Carbon::setTestNow(now()->addMinutes(16));

        $result = $this->service->resetPassword($this->email, $token, 'NewPassword123!');
        $this->assertFalse($result);
    }

    public function test_otp_rejected_after_10_minutes(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        Carbon::setTestNow(now()->addMinutes(11));

        $result = $this->service->verifyOtp($this->email, $otp);
        $this->assertNull($result);
    }

    public function test_otp_invalidated_when_new_otp_sent(): void
    {
        $this->service->sendOtp($this->email);
        $otp1 = $this->getLatestOtp($this->email);

        $this->service->sendOtp($this->email);

        $result = $this->service->verifyOtp($this->email, $otp1);
        $this->assertNull($result);
    }

    public function test_full_flow_happy_path(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        $token = $this->service->verifyOtp($this->email, $otp);
        $this->assertNotNull($token);

        $result = $this->service->resetPassword($this->email, $token, 'NewPassword123!');
        $this->assertTrue($result);

        $user = User::where('email', $this->email)->first();
        $this->assertTrue(\Hash::check('NewPassword123!', $user->password));
    }

    public function test_reset_token_reusable_after_successful_reset(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        $token = $this->service->verifyOtp($this->email, $otp);
        $this->service->resetPassword($this->email, $token, 'NewPassword123!');

        $result = $this->service->resetPassword($this->email, $token, 'AnotherPassword456!');
        $this->assertFalse($result);
    }

    public function test_otp_rejected_after_5_failed_attempts(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        for ($i = 0; $i < 5; $i++) {
            $this->service->verifyOtp($this->email, '0000');
        }

        $result = $this->service->verifyOtp($this->email, $otp);
        $this->assertNull($result);

        $failedAttempts = DB::table('password_reset_otps')
            ->where('email', $this->email)
            ->latest()
            ->value('failed_attempts');
        $this->assertEquals(5, $failedAttempts);
    }

    public function test_otp_verifies_with_less_than_5_failed_attempts(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        for ($i = 0; $i < 4; $i++) {
            $this->service->verifyOtp($this->email, '0000');
        }

        $token = $this->service->verifyOtp($this->email, $otp);
        $this->assertNotNull($token);
    }

    public function test_correct_otp_does_not_increment_failed_attempts(): void
    {
        $this->service->sendOtp($this->email);
        $otp = $this->getLatestOtp();

        $this->service->verifyOtp($this->email, $otp);

        $failedAttempts = DB::table('password_reset_otps')
            ->where('email', $this->email)
            ->latest()
            ->value('failed_attempts');
        $this->assertEquals(0, $failedAttempts);
    }

    private function getLatestOtp(): string
    {
        return Mail::sent(SendOtpMail::class)->first()->otp;
    }
}
