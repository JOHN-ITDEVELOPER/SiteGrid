<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Site;
use App\Models\SiteWorker;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $kycStatus = $request->input('kyc_status');
        $status = $request->input('status'); // active, inactive, all

        $users = User::query()
            ->with(['siteWorkers.site', 'siteMembers'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role === 'foreman', function ($query) {
                // Foremen can be identified in 3 ways:
                // 1. users.role = 'foreman'
                // 2. site_workers.is_foreman = true
                // 3. site_members.role = 'foreman'
                $query->where(function ($q) {
                    $q->where('role', 'foreman')
                        ->orWhereHas('siteWorkers', function ($sw) {
                            $sw->where('is_foreman', true)->whereNull('ended_at');
                        })
                        ->orWhereHas('siteMembers', function ($sm) {
                            $sm->where('role', 'foreman');
                        });
                });
            })
            ->when($role && $role !== 'foreman', function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($kycStatus, function ($query) use ($kycStatus) {
                $query->where('kyc_status', $kycStatus);
            })
            ->when($status === 'active', function ($query) {
                // Users with at least one active site assignment
                $query->whereHas('siteWorkers', function ($sw) {
                    $sw->whereNull('ended_at');
                });
            })
            ->when($status === 'inactive', function ($query) {
                // Users with no active site assignments but have assignments
                $query->whereHas('siteWorkers')
                    ->whereDoesntHave('siteWorkers', function ($sw) {
                        $sw->whereNull('ended_at');
                    });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Add effective_role to each user
        $users->getCollection()->transform(function ($user) {
            $user->effective_role = $this->getEffectiveRole($user);
            return $user;
        });

        return view('admin.users.index', compact('users', 'search', 'role', 'kycStatus', 'status'));
    }

    private function getEffectiveRole($user)
    {
        // Check if user is a foreman through site assignments
        $isForemanBySiteWorker = $user->siteWorkers->where('is_foreman', true)->where('ended_at', null)->isNotEmpty();
        $isForemanBySiteMember = $user->siteMembers->where('role', 'foreman')->isNotEmpty();
        
        if ($user->role === 'platform_admin') {
            return 'platform_admin';
        } elseif ($user->role === 'site_owner') {
            return 'site_owner';
        } elseif ($user->role === 'foreman' || $isForemanBySiteWorker || $isForemanBySiteMember) {
            return 'foreman';
        } else {
            return 'worker';
        }
    }

    public function create()
    {
        $sites = Site::orderBy('name')->get();
        return view('admin.users.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'role' => 'required|string|in:platform_admin,site_owner,foreman,worker',
            'password' => 'required|string|min:6',
            'kyc_status' => 'nullable|string|in:pending,approved,rejected',
        ];

        // Add site assignment validation for foreman/worker
        if (in_array($request->input('role'), ['foreman', 'worker'])) {
            $rules['site_id'] = 'required|exists:sites,id';
            $rules['daily_rate'] = 'required|numeric|min:0';
            $rules['weekly_rate'] = 'required|numeric|min:0';
            $rules['is_foreman'] = 'boolean';
            $rules['started_at'] = 'nullable|date';
        }

        $validated = $request->validate($rules);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        // Create site assignment if foreman or worker
        if (in_array($validated['role'], ['foreman', 'worker'])) {
            SiteWorker::create([
                'user_id' => $user->id,
                'site_id' => $validated['site_id'],
                'is_foreman' => $validated['is_foreman'] ?? false,
                'role' => null,
                'daily_rate' => $validated['daily_rate'],
                'weekly_rate' => $validated['weekly_rate'],
                'started_at' => $validated['started_at'] ?? now(),
            ]);
        }

        $this->logAction('user.create', 'User', $user->id, [
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'site_id' => $validated['site_id'] ?? null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        $user->load([
            'ownedSites', 
            'siteWorkers' => function ($query) {
                $query->with('site')->orderBy('created_at', 'desc');
            },
            'payouts'
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'role' => 'required|string|in:platform_admin,site_owner,foreman,worker',
            'password' => 'nullable|string|min:6',
            'kyc_status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        $this->logAction('user.update', 'User', $user->id, [
            'role' => $validated['role'],
            'kyc_status' => $validated['kyc_status'] ?? null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->delete();

        $this->logAction('user.delete', 'User', $user->id, [
            'email' => $user->email,
            'phone' => $user->phone,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully');
    }

    public function export(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $kycStatus = $request->input('kyc_status');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($kycStatus, function ($query) use ($kycStatus) {
                $query->where('kyc_status', $kycStatus);
            })
            ->orderByDesc('created_at')
            ->get();

        $csv = "Name,Email,Phone,Role,KYC Status,Joined\n";
        foreach ($users as $user) {
            $csv .= "\"{$user->name}\",\"{$user->email}\",\"{$user->phone}\",\"{$user->role}\",\"{$user->kyc_status}\"," . $user->created_at->format('Y-m-d') . "\n";
        }

        $this->logAction('users.export', null, null, ['count' => $users->count()]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-export-' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve_kyc,reject_kyc,suspend,reactivate,delete',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->user_ids;
        $action = $request->action;

        switch ($action) {
            case 'approve_kyc':
                User::whereIn('id', $userIds)->update(['kyc_status' => 'approved']);
                $this->logAction('users.bulk_approve_kyc', null, null, ['count' => count($userIds)]);
                return back()->with('success', count($userIds) . ' users KYC approved.');

            case 'reject_kyc':
                User::whereIn('id', $userIds)->update(['kyc_status' => 'rejected']);
                $this->logAction('users.bulk_reject_kyc', null, null, ['count' => count($userIds)]);
                return back()->with('success', count($userIds) . ' users KYC rejected.');

            case 'suspend':
                // Don't suspend self
                $userIds = array_diff($userIds, [auth()->id()]);
                User::whereIn('id', $userIds)->update([
                    'is_suspended' => true,
                    'suspension_reason' => 'Bulk suspended by admin',
                    'suspended_at' => now(),
                    'suspended_by' => auth()->id(),
                ]);
                $this->logAction('users.bulk_suspend', null, null, ['count' => count($userIds)]);
                return back()->with('success', count($userIds) . ' users suspended.');

            case 'reactivate':
                User::whereIn('id', $userIds)->update([
                    'is_suspended' => false,
                    'suspension_reason' => null,
                    'suspended_at' => null,
                    'suspended_by' => null,
                ]);
                $this->logAction('users.bulk_reactivate', null, null, ['count' => count($userIds)]);
                return back()->with('success', count($userIds) . ' users reactivated.');

            case 'delete':
                // Don't delete self
                $userIds = array_diff($userIds, [auth()->id()]);
                User::whereIn('id', $userIds)->delete();
                $this->logAction('users.bulk_delete', null, null, ['count' => count($userIds)]);
                return back()->with('success', count($userIds) . ' users deleted.');

            default:
                return back()->with('error', 'Invalid action.');
        }
    }

    public function storeSiteForOwner(Request $request, User $user)
    {
        if ($user->role !== 'site_owner') {
            return back()->withErrors(['user' => 'Sites can only be created for users with Site Owner role.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'payout_method' => 'required|in:platform_managed,owner_managed',
            'owner_mpesa_account' => 'nullable|string|max:50|required_if:payout_method,owner_managed',
        ]);

        $settings = PlatformSetting::firstOrCreate([]);
        
        $site = Site::create([
            'owner_id' => $user->id,
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'payout_method' => $validated['payout_method'],
            'owner_mpesa_account' => $validated['owner_mpesa_account'] ?? null,
            'is_completed' => false,
            'invoice_payment_method' => 'auto_wallet',
            'invoice_due_days' => $settings->default_invoice_due_days ?? 14,
        ]);

        $this->logAction('admin.site.create_for_owner', 'Site', $site->id, [
            'owner_id' => $user->id,
            'owner_name' => $user->name,
            'site_name' => $site->name,
            'payout_method' => $site->payout_method,
        ]);

        return back()->with('success', 'Site created for ' . $user->name . ' successfully.');
    }

    /**
     * Suspend a user account
     */
    public function suspend(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot suspend your own account.']);
        }

        if ($user->is_suspended) {
            return back()->withErrors(['user' => 'User is already suspended.']);
        }

        $validated = $request->validate([
            'suspension_reason' => 'required|string|max:500',
        ]);

        $user->update([
            'is_suspended' => true,
            'suspension_reason' => $validated['suspension_reason'],
            'suspended_at' => now(),
            'suspended_by' => auth()->id(),
        ]);

        $this->logAction('user.suspend', 'User', $user->id, [
            'reason' => $validated['suspension_reason'],
        ]);

        return back()->with('success', 'User suspended successfully.');
    }

    /**
     * Reactivate a suspended user account
     */
    public function reactivateUser(User $user)
    {
        if (!$user->is_suspended) {
            return back()->withErrors(['user' => 'User is not suspended.']);
        }

        $user->update([
            'is_suspended' => false,
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);

        $this->logAction('user.reactivate', 'User', $user->id, []);

        return back()->with('success', 'User reactivated successfully.');
    }

    /**
     * Force a user to reset their password on next login
     */
    public function forcePasswordReset(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot force password reset on your own account.']);
        }

        $user->update(['password_reset_required' => true]);

        $this->logAction('user.force_password_reset', 'User', $user->id, []);

        return back()->with('success', 'User will be required to reset password on next login.');
    }

    /**
     * View user activity logs
     */
    public function activity(User $user)
    {
        $logs = AuditLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.users.activity', compact('user', 'logs'));
    }

    private function logAction(string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
