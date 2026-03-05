<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Request OTP
     * POST /api/v1/auth/request-otp
     */
    public function requestOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
        ]);

        try {
            $this->otpService->generateOtp($validated['phone']);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'phone' => $validated['phone'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify OTP and authenticate
     * POST /api/v1/auth/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'otp_code' => 'required|string|size:6',
            'name' => 'nullable|string|max:255',
        ]);

        // Verify OTP
        if (!$this->otpService->verifyOtp($validated['phone'], $validated['otp_code'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Find or create user
        $user = User::firstOrCreate(
            ['phone' => $validated['phone']],
            [
                'name' => $validated['name'] ?? null,
                'role' => 'worker',
                'kyc_status' => 'pending',
            ]
        );

        // Check if user is suspended
        if ($user->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Reason: ' . $user->suspension_reason,
                'account_suspended' => true,
            ], 403);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Resend OTP
     * POST /api/v1/auth/resend-otp
     */
    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
        ]);

        try {
            $this->otpService->resendOtp($validated['phone']);

            return response()->json([
                'success' => true,
                'message' => 'OTP resent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get current user
     * GET /api/v1/auth/me
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
}
