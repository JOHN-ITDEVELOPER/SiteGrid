<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request)
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
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'role', 'kycStatus'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'role' => 'required|string|in:platform_admin,site_owner,foreman,worker',
            'password' => 'required|string|min:6',
            'kyc_status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $this->logAction('user.create', 'User', $user->id, [
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        $user->load(['ownedSites', 'siteWorkers.site', 'payouts']);

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
            'action' => 'required|in:approve_kyc,reject_kyc,delete',
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

        $site = Site::create([
            'owner_id' => $user->id,
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'payout_method' => $validated['payout_method'],
            'owner_mpesa_account' => $validated['owner_mpesa_account'] ?? null,
            'is_completed' => false,
        ]);

        $this->logAction('admin.site.create_for_owner', 'Site', $site->id, [
            'owner_id' => $user->id,
            'owner_name' => $user->name,
            'site_name' => $site->name,
            'payout_method' => $site->payout_method,
        ]);

        return back()->with('success', 'Site created for ' . $user->name . ' successfully.');
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
