<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Show the registration page
     */
    public function show()
    {
        return view('auth.register');
    }

    /**
     * Handle email/password registration
     * POST /register/email
     */
    public function registerEmail(Request $request)
    {
        $isJsonRequest = $request->expectsJson();

        try {
            $validated = $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'site_name' => 'required|string|max:100',
                'terms' => 'required|accepted',
            ], [
                'email.required' => 'Email address is required',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'This email is already registered',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'password.confirmed' => 'Passwords do not match',
                'site_name.required' => 'Site name is required',
                'terms.required' => 'You must accept the terms and conditions',
                'terms.accepted' => 'You must accept the terms and conditions',
            ]);

            // Create user with unverified email
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'name' => null, // Will be filled after email verification
                'phone' => null, // Will be filled during profile completion
                'site_name' => $validated['site_name'],
                'role' => 'site_owner',
                'kyc_status' => 'pending',
                'email_verified_at' => null, // Email not yet verified
            ]);

            // Send email verification notification
            event(new Registered($user));

            $successMsg = 'Account created! Check your email to verify your account.';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect' => route('login'),
                ]);
            }
            
            return redirect(route('login'))->with('status', '✓ Account created! Check your email to verify your account and login.');

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
                    'message' => 'Registration failed: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['email' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Verify email address
     * GET /email/verify/{id}/{hash}
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Verify the hash matches
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect(route('login'))->withErrors(['email' => 'Invalid verification link']);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        // Redirect to login with success message  
        return redirect(route('login'))->with('verified_success', true);
    }

    /**
     * Resend email verification
     * POST /email/resend
     */
    public function resendVerificationEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Verification email sent! Check your inbox.');
    }
}
