<?php

namespace App\Services;

use App\Models\OtpSession;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OtpService
{
    const OTP_LENGTH = 6;
    const OTP_EXPIRY_MINUTES = 5;
    const MAX_ATTEMPTS = 3;

    /**
     * Generate and send OTP
     */
    public function generateOtp(string $phone): OtpSession
    {
        // Invalidate previous OTP for this phone
        OtpSession::where('phone', $phone)->delete();

        // Generate new OTP
        $otpCode = str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        // Create session
        $otpSession = OtpSession::create([
            'phone' => $phone,
            'otp_code' => $otpCode,
            'attempts' => 0,
            'verified' => false,
            'expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        // TODO: Send SMS via Africa's Talking
        // $this->sendSms($phone, "Your SiteGrid verification code is: {$otpCode}");

        // For MVP, log to console
        \Log::info("OTP for {$phone}: {$otpCode}");

        return $otpSession;
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $phone, string $otpCode): bool
    {
        $session = OtpSession::where('phone', $phone)->first();

        if (!$session) {
            return false;
        }

        // Check expiry
        if (Carbon::now()->isAfter($session->expires_at)) {
            $session->delete();
            return false;
        }

        // Check max attempts
        if ($session->attempts >= self::MAX_ATTEMPTS) {
            $session->delete();
            return false;
        }

        // Verify code
        if ($session->otp_code !== $otpCode) {
            $session->increment('attempts');
            return false;
        }

        // Mark as verified
        $session->update(['verified' => true]);
        return true;
    }

    /**
     * Resend OTP
     */
    public function resendOtp(string $phone): OtpSession
    {
        return $this->generateOtp($phone);
    }

    /**
     * Clean expired OTPs (run via cron)
     */
    public function cleanExpiredOtps(): void
    {
        OtpSession::where('expires_at', '<', Carbon::now())->delete();
    }
}
