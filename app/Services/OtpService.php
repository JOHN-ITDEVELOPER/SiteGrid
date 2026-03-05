<?php

namespace App\Services;

use App\Models\OtpSession;
use Carbon\Carbon;

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

        // Send SMS via Africa's Talking
        try {
            $this->sendSms($phone, "Your SiteGrid verification code is: {$otpCode}. Valid for " . self::OTP_EXPIRY_MINUTES . " minutes.");
        } catch (\Exception $e) {
            \Log::error("Failed to send OTP SMS to {$phone}: " . $e->getMessage());
            // Continue anyway - log shows the OTP for development
        }

        // For development, also log to console
        if (app()->environment('local', 'development')) {
            \Log::info("OTP for {$phone}: {$otpCode}");
        }

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
    /**
     * Send SMS via Africa's Talking
     */
    private function sendSms(string $phone, string $message): bool
    {
        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.api_key');
        $from = config('services.africastalking.from', 'SITEGRID');

        if (empty($username) || empty($apiKey)) {
            \Log::warning('Africa\'s Talking credentials not configured');
            return false;
        }

        try {
            $client = new \GuzzleHttp\Client();
            
            $response = $client->post('https://api.africastalking.com/version1/messaging', [
                'headers' => [
                    'apiKey' => $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'username' => $username,
                    'to' => $phone,
                    'message' => $message,
                    'from' => $from,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            \Log::info('SMS sent via Africa\'s Talking', [
                'phone' => $phone,
                'result' => $result,
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Africa\'s Talking SMS Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification SMS (for payment confirmations, etc.)
     */
    public function sendNotification(string $phone, string $message): bool
    {
        return $this->sendSms($phone, $message);
    }}
