<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Mjengo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-900 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">M</span>
                    </div>
                    <span class="text-xl font-bold text-indigo-900">Mjengo</span>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-gray-700">{{ auth()->user()->name ?? 'User' }}</span>
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-indigo-900">Welcome back, {{ auth()->user()->name ?? 'User' }}!</h1>
            <p class="text-gray-600 mt-2">Manage your sites, workers, and payroll all in one place.</p>
        </div>

        <!-- Quick Actions -->
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            <a href="#" class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                <h3 class="text-lg font-bold text-indigo-900 mb-2">Create a new site</h3>
                <p class="text-gray-600 text-sm">Set up a new construction site and add workers.</p>
                <div class="mt-4 text-orange-500 font-semibold">→ Get started</div>
            </a>

            <a href="#" class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                <h3 class="text-lg font-bold text-indigo-900 mb-2">View my sites</h3>
                <p class="text-gray-600 text-sm">Manage workers, mark attendance, and process payroll.</p>
                <div class="mt-4 text-orange-500 font-semibold">→ View all</div>
            </a>

            <a href="#" class="bg-white p-6 rounded-lg border border-gray-200 hover:shadow-lg transition">
                <h3 class="text-lg font-bold text-indigo-900 mb-2">Account settings</h3>
                <p class="text-gray-600 text-sm">Update your profile, payment method, and preferences.</p>
                <div class="mt-4 text-orange-500 font-semibold">→ Settings</div>
            </a>
        </div>

        <!-- Getting Started Section -->
        <div class="bg-gradient-to-r from-indigo-50 to-orange-50 p-8 rounded-lg border border-indigo-200">
            <h2 class="text-2xl font-bold text-indigo-900 mb-4">Getting started with Mjengo</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="inline-block p-3 bg-orange-100 rounded-lg mb-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-indigo-900 mb-2">Step 1: Create a site</h3>
                    <p class="text-gray-700 text-sm">Give your site a name, location, and daily worker rates.</p>
                </div>

                <div>
                    <div class="inline-block p-3 bg-orange-100 rounded-lg mb-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM6 20a7 7 0 1112 0" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-indigo-900 mb-2">Step 2: Add workers</h3>
                    <p class="text-gray-700 text-sm">Register your team with phone numbers for USSD check-in.</p>
                </div>

                <div>
                    <div class="inline-block p-3 bg-orange-100 rounded-lg mb-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-indigo-900 mb-2">Step 3: Compute & payout</h3>
                    <p class="text-gray-700 text-sm">Review weekly pay, approve, and disburse via M-Pesa.</p>
                </div>
            </div>

            <button class="mt-6 px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition">
                Create your first site →
            </button>
        </div>

        <!-- Placeholder: Recent Activity -->
        <div class="mt-12 bg-white p-6 rounded-lg border border-gray-200">
            <h2 class="text-lg font-bold text-indigo-900 mb-4">Recent activity</h2>
            <p class="text-gray-500">No sites created yet. 
                <a href="#" class="text-orange-600 hover:text-orange-700 font-semibold">Create your first site →</a>
            </p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-8 mt-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm">
            <p>© 2026 Mjengo. All rights reserved.</p>
            <p class="mt-2">
                <a href="#" class="hover:text-white">Docs</a> • 
                <a href="#" class="hover:text-white">Support</a> • 
                <a href="#" class="hover:text-white">Privacy</a>
            </p>
        </div>
    </footer>
</body>
</html>
