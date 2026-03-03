<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Show profile completion page
     */
    public function complete()
    {
        return view('auth.complete-profile');
    }

    /**
     * Store profile information
     */
    public function store(Request $request)
    {
        $isJsonRequest = $request->expectsJson();

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'phone' => 'nullable|string|regex:/^\+?[0-9]{9,15}$/',
                'company_name' => 'nullable|string|max:100',
            ], [
                'name.required' => 'Full name is required',
                'name.max' => 'Name must not exceed 100 characters',
                'phone.regex' => 'Please enter a valid phone number',
            ]);

            // Update user profile
            $user = Auth::user();
            $user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? $user->phone,
            ]);

            $successMsg = 'Profile completed successfully!';
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect' => route('owner.dashboard'),
                ]);
            }

            return redirect(route('owner.dashboard'))->with('success', $successMsg);

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
                    'message' => 'Failed to update profile: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['name' => 'Failed to update profile']);
        }
    }
}
