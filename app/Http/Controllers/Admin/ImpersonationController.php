<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    public function impersonate(User $user)
    {
        if ($user->role === 'platform_admin') {
            return back()->with('error', 'Cannot impersonate other admins.');
        }

        // Store original admin ID
        Session::put('impersonate_from', Auth::id());
        
        // Log to activity
        ActivityLog::create([
            'type' => 'user_impersonation_start',
            'severity' => 'warning',
            'message' => Auth::user()->name . ' started impersonating ' . $user->name,
            'user_id' => Auth::id(),
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'meta' => [
                'admin' => Auth::user()->name,
                'target_user' => $user->name,
                'target_role' => $user->role,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Switch to target user
        Auth::login($user);

        // Redirect based on role
        switch ($user->role) {
            case 'site_owner':
                return redirect()->route('owner.dashboard');
            case 'foreman':
                return redirect()->route('foreman.dashboard');
            case 'worker':
                return redirect()->route('worker.dashboard');
            default:
                return redirect('/');
        }
    }

    public function leave()
    {
        $originalAdminId = Session::get('impersonate_from');

        if (!$originalAdminId) {
            return redirect()->route('admin.dashboard');
        }

        $currentUser = Auth::user();
        $originalAdmin = User::find($originalAdminId);

        // Log end of impersonation
        ActivityLog::create([
            'type' => 'user_impersonation_end',
            'severity' => 'info',
            'message' => 'Impersonation of ' . $currentUser->name . ' ended',
            'user_id' => $originalAdminId,
            'entity_type' => 'User',
            'entity_id' => $currentUser->id,
            'meta' => [
                'admin' => $originalAdmin->name,
                'target_user' => $currentUser->name,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Return to admin
        Auth::login($originalAdmin);
        Session::forget('impersonate_from');

        return redirect()->route('admin.dashboard')->with('success', 'Stopped impersonating ' . $currentUser->name);
    }
}
