<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccountSuspension
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user is suspended
            if ($user->is_suspended) {
                // Logout the suspended user
                Auth::logout();
                $request->session()->flush();
                $request->session()->regenerate();
                
                // Redirect to login with error message
                return redirect()->route('login')->withErrors([
                    'login' => 'Your account has been suspended. Reason: ' . ($user->suspension_reason ?? 'Account suspended by administrator'),
                ]);
            }
            
            // Check if password reset is required
            if ($user->password_reset_required && !$request->routeIs('password.*', 'logout')) {
                return redirect()->route('password.reset')->with('warning', 'You are required to reset your password before continuing.');
            }
        }
        
        return $next($request);
    }
}
