<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    /**
     * Show the landing page
     */
    public function index()
    {
        return view('landing');
    }

    /**
     * Handle phone number submission for signup
     * Initiates OTP sending process
     */
    public function submitPhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:9|max:13',
            'name' => 'nullable|string|max:255',
        ]);

        // Normalize phone number
        $phone = preg_replace('/[^0-9+]/', '', $validated['phone']);
        
        // Ensure phone starts with + for international format
        if (!str_starts_with($phone, '+')) {
            $phone = '+254' . ltrim($phone, '0');
        }

        // Log the signup initiation for analytics
        Log::info('Signup initiated', [
            'phone' => $phone,
            'name' => $validated['name'] ?? null,
            'ip' => $request->ip(),
        ]);

        // TODO: Integrate with Africa's Talking or Twilio to send OTP
        // For MVP, generate a 6-digit code and store in session
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store in session with expiry (5 minutes)
        session([
            'signup_phone' => $phone,
            'signup_name' => $validated['name'],
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // TODO: Send OTP via USSD provider
        // Example: AfrikaTalking::sms()->send($phone, "Your SiteGrid verification code is: $otp");
        // For development, log the OTP
        Log::info('OTP generated', ['phone' => $phone, 'otp' => $otp]);

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent',
            'phone' => $phone,
        ]);
    }

    /**
     * Verify OTP and create site/user
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        // Retrieve session data
        $sessionPhone = session('signup_phone');
        $sessionOtp = session('otp_code');
        $sessionName = session('signup_name');
        $expiresAt = session('otp_expires_at');

        // Validate OTP
        if (!$sessionPhone || $sessionPhone !== $validated['phone']) {
            Log::warning('OTP verification failed: phone mismatch');
            return response()->json([
                'success' => false,
                'message' => 'Phone number mismatch',
            ], 400);
        }

        if (!$sessionOtp || $sessionOtp !== $validated['otp_code']) {
            Log::warning('OTP verification failed: invalid code', ['phone' => $validated['phone']]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code',
            ], 400);
        }

        if (now()->isAfter($expiresAt)) {
            Log::warning('OTP verification failed: expired', ['phone' => $validated['phone']]);
            return response()->json([
                'success' => false,
                'message' => 'Verification code expired. Please request a new one.',
            ], 400);
        }

        // Check if user already exists
        $user = User::where('phone', $validated['phone'])->first();

        if ($user) {
            // User exists, update and log in
            if ($sessionName) {
                $user->update(['name' => $sessionName]);
            }
        } else {
            // Create new user
            $user = User::create([
                'phone' => $validated['phone'],
                'name' => $sessionName ?? 'Site Owner',
                'email' => 'user_' . time() . '@sitegrid.local', // Temporary email
                'password' => bcrypt(uniqid()), // Random password
            ]);
        }

        // Clear session data
        session()->forget(['signup_phone', 'signup_name', 'otp_code', 'otp_expires_at']);

        // Log successful signup
        Log::info('User verified via OTP', [
            'user_id' => $user->id,
            'phone' => $validated['phone'],
        ]);

        // Authenticate user
        auth()->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Account verified',
            'redirect' => '/dashboard',
        ]);
    }

    /**
     * Handle demo request form
     */
    public function submitDemo(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'nullable|string|max:1000',
        ]);

        // Log demo request
        Log::info('Demo request submitted', [
            'name' => $validated['name'],
            'company' => $validated['company'] ?? 'Not provided',
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'ip' => $request->ip(),
        ]);

        // TODO: Store demo request in database
        // DemoRequest::create($validated);

        // TODO: Send email notification to sales team
        // Mail::to(config('mail.from.address'))->send(new DemoRequestNotification($validated));

        // Track event for analytics
        Log::info('demo_requested', ['email' => $validated['email']]);

        return response()->json([
            'success' => true,
            'message' => 'Demo request received. We\'ll contact you shortly.',
        ]);
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $phone = session('signup_phone');

        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please start again.',
            ], 400);
        }

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Update session
        session([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // TODO: Send OTP via USSD provider
        Log::info('OTP resent', ['phone' => $phone, 'otp' => $otp]);

        return response()->json([
            'success' => true,
            'message' => 'Verification code resent',
        ]);
    }
}
