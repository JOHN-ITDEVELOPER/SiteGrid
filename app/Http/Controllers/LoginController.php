<?php

namespace App\Http\Controllers;

use App\Models\SiteMember;
use App\Models\SiteWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Show the login page
     */
    public function showLoginPage()
    {
        return view('auth.login');
    }

    /**
     * Handle login with phone/email and password
     */
    public function login(Request $request)
    {
        // Check if this is a JSON request
        $isJsonRequest = $request->header('Content-Type') === 'application/json' || $request->expectsJson();

        try {
            $validated = $request->validate([
                'login' => 'required|string',
                'password' => 'required|string|min:6',
            ], [
                'login.required' => 'Please enter your phone number or email',
                'password.required' => 'Please enter your password',
            ]);

            $login = $validated['login'];
            $password = $validated['password'];

            // Find user by phone or email
            $user = \App\Models\User::where('phone', $login)
                ->orWhere('email', $login)
                ->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($password, $user->password)) {
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid phone/email or password',
                    ], 401);
                }
                return back()->withErrors(['login' => 'Invalid phone/email or password']);
            }

            // Check if account is suspended
            if ($user->is_suspended) {
                $errorMsg = 'Your account has been suspended. Reason: ' . $user->suspension_reason;
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'account_suspended' => true,
                    ], 403);
                }
                return back()->withErrors(['login' => $errorMsg]);
            }

            // Check if email is verified (if user has email)
            if ($user->email && !$user->hasVerifiedEmail()) {
                $errorMsg = 'Please verify your email before logging in. Check your inbox for the verification link.';
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'email_unverified' => true,
                    ], 403);
                }
                return back()->withErrors(['login' => $errorMsg]);
            }

            // Login the user
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // Check if password reset is required
            if ($user->password_reset_required) {
                return redirect()->route('password.reset')->with('warning', 'You are required to reset your password before continuing.');
            }

            // Check if user needs to complete profile (name is required)
            if (is_null($user->name)) {
                $redirectPath = route('profile.complete');
            } else {
                $redirectPath = $this->getDashboardPath($user);
            }

            // Return JSON for AJAX requests
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => $redirectPath,
                ]);
            }

            return redirect($redirectPath)->with('success', 'Welcome back!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Determine appropriate dashboard based on role
     */
    private function getDashboardPath($user)
    {
        $hasForemanAssignment = SiteWorker::where('user_id', $user->id)
            ->where('is_foreman', true)
            ->whereNull('ended_at')
            ->exists()
            || SiteMember::where('user_id', $user->id)
                ->where('role', 'foreman')
                ->exists();

        if ($hasForemanAssignment) {
            return route('foreman.dashboard');
        }

        return match ($user->role) {
            'platform_admin' => route('admin.dashboard'),
            'site_owner' => route('owner.dashboard'),
            'foreman' => route('foreman.dashboard'),
            'worker' => route('worker.dashboard'),
            default => '/dashboard',
        };
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->flush();

        return redirect()->route('landing')->with('success', 'You have been logged out');
    }
}
