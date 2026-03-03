<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function edit(): View
    {
        $owner = auth()->user();

        $sites = $owner->ownedSites()
            ->select('id', 'name', 'billing_plan', 'created_at', 'updated_at')
            ->withCount(['workers' => function ($query) {
                $query->whereNull('ended_at');
            }])
            ->orderBy('name')
            ->get();

        $platformSettings = PlatformSetting::first();

        return view('owner.account.settings', [
            'owner' => $owner,
            'sites' => $sites,
            'platformSettings' => $platformSettings,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $owner = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $owner->id],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $owner->id],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $owner->update($data);

        return back()->with('success', 'Profile settings updated.');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $owner = auth()->user();

        $validated = $request->validate([
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', 'max:10'],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.*' => ['in:sms,email,push'],
        ]);

        $owner->update([
            'timezone' => $validated['timezone'],
            'locale' => $validated['locale'],
            'notification_preferences' => $validated['notification_preferences'] ?? [],
        ]);

        return back()->with('success', 'Preferences updated.');
    }
}
