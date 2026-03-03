<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Show the forgot password form or reset form (if token provided)
     */
    public function show(Request $request, $token = null)
    {
        // If token is provided as URL parameter or query param, show reset password form
        $token = $token ?? $request->query('token');
        $email = $request->query('email');
        
        if ($token && $email) {
            return view('auth.reset-password', [
                'token' => $token,
                'email' => $email,
            ]);
        }

        return view('auth.forgot-password');
    }

    /**
     * Send password reset link via email
     * POST /forgot-password
     */
    public function sendResetLink(Request $request)
    {
        $isJsonRequest = $request->expectsJson();

        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ], [
                'email.required' => 'Email address is required',
                'email.email' => 'Please enter a valid email address',
            ]);

            // Find user by email
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // For security, don't reveal if email exists
                $message = 'If an account exists with this email, a password reset link will be sent.';
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                    ]);
                }
                return back()->with('status', $message);
            }

            // Generate reset token
            $token = Str::random(60);
            
            // Store token in database (you'll need to add a password_resets table)
            \DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Send password reset email
            try {
                \Mail::send('emails.password-reset', [
                    'user' => $user,
                    'resetLink' => route('password.reset', ['token' => $token, 'email' => $user->email]),
                    'expiresIn' => '60 minutes',
                ], function ($message) use ($user) {
                    $message->to($user->email)
                            ->subject('SiteGrid - Password Reset Request');
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send password reset email: ' . $e->getMessage());
            }

            $message = 'If an account exists with this email, a password reset link will be sent.';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            return back()->with('status', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Handle password reset form submission (from email link)
     */
    public function reset(Request $request)
    {
        $isJsonRequest = $request->expectsJson();

        try {
            $validated = $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'token.required' => 'Reset token is missing',
                'email.required' => 'Email is required',
                'email.email' => 'Invalid email address',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'password.confirmed' => 'Passwords do not match',
            ]);

            // Check if reset token is valid
            $resetRecord = \DB::table('password_resets')
                ->where('email', $validated['email'])
                ->first();

            if (!$resetRecord) {
                $errorMsg = 'Invalid or expired password reset link';
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 401);
                }
                return back()->withErrors(['email' => $errorMsg]);
            }

            // Verify token
            if (!Hash::check($validated['token'], $resetRecord->token)) {
                $errorMsg = 'Invalid or expired password reset link';
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 401);
                }
                return back()->withErrors(['email' => $errorMsg]);
            }

            // Check if token is expired (60 minutes)
            if (now()->diffInMinutes($resetRecord->created_at) > 60) {
                \DB::table('password_resets')->where('email', $validated['email'])->delete();
                $errorMsg = 'Password reset link has expired. Please request a new one.';
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 401);
                }
                return back()->withErrors(['email' => $errorMsg]);
            }

            // Update password
            $user = User::where('email', $validated['email'])->firstOrFail();
            $user->password = Hash::make($validated['password']);
            $user->save();

            // Delete reset token
            \DB::table('password_resets')->where('email', $validated['email'])->delete();

            // Fire password reset event
            event(new PasswordReset($user));

            $successMsg = 'Your password has been reset successfully. You can now log in.';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect' => route('login'),
                ]);
            }
            return redirect(route('login'))->with('status', $successMsg);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password reset failed: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['email' => 'Password reset failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle phone OTP password reset
     * POST /forgot-password/phone
     */
    public function sendPhoneOtp(Request $request)
    {
        $isJsonRequest = $request->expectsJson();

        try {
            // Check if this is request to send OTP or reset password with OTP
            if ($request->has('phone') && !$request->has('otp_code')) {
                // Step 1: Send OTP
                return $this->sendOtpForPhone($request, $isJsonRequest);
            } elseif ($request->has('phone') && $request->has('otp_code') && !$request->has('password')) {
                // Step 2: Verify OTP
                return $this->verifyPhoneOtp($request, $isJsonRequest);
            } elseif ($request->has('phone') && $request->has('otp_code') && $request->has('password')) {
                // Step 3: Reset password
                return $this->resetPasswordWithOtp($request, $isJsonRequest);
            }

            $errorMsg = 'Invalid request';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 422);
            }
            return back()->withErrors(['phone' => $errorMsg]);

        } catch (\Exception $e) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['phone' => $e->getMessage()]);
        }
    }

    /**
     * Send OTP to phone for password reset
     */
    private function sendOtpForPhone(Request $request, $isJsonRequest)
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^\+?[0-9]{9,15}$/',
        ], [
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Please enter a valid phone number',
        ]);

        // Check if user exists
        $user = User::where('phone', $validated['phone'])->first();
        if (!$user) {
            $message = 'If an account exists with this phone number, a verification code will be sent.';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            return back()->with('status', $message);
        }

        try {
            // Generate and send OTP
            $this->otpService->generateOtp($validated['phone']);

            $message = 'Verification code sent to ' . $validated['phone'];
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'phone' => $validated['phone'],
                ]);
            }
            return back()->with('status', $message)
                        ->with('phone', $validated['phone']);

        } catch (\Exception $e) {
            $errorMsg = 'Failed to send verification code: ' . $e->getMessage();
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 500);
            }
            return back()->withErrors(['phone' => $errorMsg]);
        }
    }

    /**
     * Verify OTP for phone password reset
     */
    private function verifyPhoneOtp(Request $request, $isJsonRequest)
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^\+?[0-9]{9,15}$/',
            'otp_code' => 'required|string|size:6',
        ], [
            'otp_code.required' => 'Verification code is required',
            'otp_code.size' => 'Verification code must be 6 digits',
        ]);

        // Verify OTP
        if (!$this->otpService->verifyOtp($validated['phone'], $validated['otp_code'])) {
            $errorMsg = 'Invalid or expired verification code';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 401);
            }
            return back()->withErrors(['otp_code' => $errorMsg])->withInput();
        }

        $message = 'Phone number verified. Please set your new password.';
        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'phone' => $validated['phone'],
                'otp_code' => $validated['otp_code'],
            ]);
        }
        return back()->with('status', $message)
                    ->with('phone', $validated['phone'])
                    ->with('otp_code', $validated['otp_code']);
    }

    /**
     * Reset password with OTP
     */
    private function resetPasswordWithOtp(Request $request, $isJsonRequest)
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^\+?[0-9]{9,15}$/',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Passwords do not match',
        ]);

        // Find user by phone
        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            $errorMsg = 'User not found';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 404);
            }
            return back()->withErrors(['phone' => $errorMsg]);
        }

        // Update password
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Fire password reset event
        event(new PasswordReset($user));

        $successMsg = 'Your password has been reset successfully. You can now log in.';
        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'redirect' => route('login'),
            ]);
        }
        return redirect(route('login'))->with('status', $successMsg);
    }
}
