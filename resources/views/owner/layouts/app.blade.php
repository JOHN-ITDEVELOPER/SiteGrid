<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>@yield('title', 'Owner Dashboard') - SiteGrid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --brand-indigo: #1e1b4b;
            --brand-orange: #f97316;
            --bg: #f8fafc;
        }

        body {
            background: var(--bg);
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
        }

        .owner-sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--brand-indigo), #312e81);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            padding: 1.25rem 0.75rem;
            height: 100vh;
            overflow-y: auto;
        }

        .owner-brand {
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0.5rem 0.75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            margin-bottom: 0.75rem;
            text-align: center;
        }

        .nav-section {
            padding: 0.75rem 0;
        }

        .nav-section-title {
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 0.75rem 0.75rem;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .owner-sidebar a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.65rem 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .owner-sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left-color: var(--brand-orange);
        }

        .owner-sidebar a.active {
            background: rgba(249, 115, 22, 0.18);
            color: #fff;
            border-left-color: var(--brand-orange);
        }

        .owner-main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .owner-topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .owner-content {
            padding: 1.5rem;
        }

        .kpi-card {
            border: 0;
            border-radius: 0.9rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .sidebar-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--brand-indigo);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 900;
        }

        @media (max-width: 768px) {
            .owner-sidebar {
                position: fixed;
                left: 0;
                width: 60vw;
                max-width: 320px;
                min-width: 240px;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .owner-sidebar.is-open {
                transform: translateX(0);
            }

            .sidebar-overlay.is-open {
                opacity: 1;
                pointer-events: auto;
            }

            .owner-main {
                margin-left: 0;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="owner-sidebar" id="ownerSidebar">
        <div class="owner-brand">SiteGrid Owner</div>
        
        <div class="nav-section">
            <div class="nav-section-title">Dashboard</div>
            <a href="{{ route('owner.dashboard') }}" class="{{ request()->routeIs('owner.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
            <a href="{{ route('owner.sites') }}" class="{{ request()->routeIs('owner.sites') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                Sites
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Operations</div>
            <a href="{{ route('owner.workforce') }}" class="{{ request()->routeIs('owner.workforce') || request()->routeIs('owner.workers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                Workforce
            </a>
            <a href="{{ route('owner.attendance') }}" class="{{ request()->routeIs('owner.attendance') ? 'active' : '' }}">
                <i class="bi bi-check2-square"></i>
                Attendance
            </a>
            <a href="{{ route('owner.payroll') }}" class="{{ request()->routeIs('owner.payroll') || request()->routeIs('owner.paycycles.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                Payroll
            </a>
            <a href="{{ route('owner.wallet') }}" class="{{ request()->routeIs('owner.wallet') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i>
                Wallet
            </a>
            <a href="{{ route('owner.inventory.index') }}" class="{{ request()->routeIs('owner.inventory.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                Inventory
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <a href="{{ route('owner.claims') }}" class="{{ request()->routeIs('owner.claims') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i>
                Claims & Requests
            </a>
            <a href="{{ route('owner.invoices') }}" class="{{ request()->routeIs('owner.invoices') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i>
                Invoices
            </a>
            <a href="{{ route('owner.disputes') }}" class="{{ request()->routeIs('owner.disputes') ? 'active' : '' }}">
                <i class="bi bi-shield-exclamation"></i>
                Disputes & Escrow
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Settings</div>
            <a href="{{ route('owner.account.settings') }}" class="{{ request()->routeIs('owner.account.settings*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                Account Settings
            </a>
        </div>
    </div>

    <main class="owner-main">
        <header class="owner-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="sidebar-toggle d-md-none" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h5 class="mb-0">@yield('page-title', 'Owner Dashboard')</h5>
                    <small class="text-muted">Site owner operations center</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge text-bg-light border">{{ now()->format('D, d M Y') }}</span>
                <span class="fw-semibold">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="mb-0" id="logoutForm">
                    @csrf
                    <button type="button" data-bs-toggle="modal" data-bs-target="#logoutModal" class="btn btn-sm btn-outline-danger" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </header>

        <section class="owner-content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Action failed:</div>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </section>
    </main>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="logoutModalLabel">
                        <i class="bi bi-box-arrow-right text-warning"></i> Confirm Logout
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to logout from your account?</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('logoutForm').submit()">
                        <i class="bi bi-box-arrow-right"></i> Yes, Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ownerSidebar = document.getElementById('ownerSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            if (!ownerSidebar) {
                return;
            }
            ownerSidebar.classList.toggle('is-open');
            overlay.classList.toggle('is-open');
        }

        if (toggleBtn && overlay) {
            toggleBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        }

        // Close sidebar when a link is clicked on mobile
        if (ownerSidebar) {
            ownerSidebar.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        ownerSidebar.classList.remove('is-open');
                        overlay.classList.remove('is-open');
                    }
                });
            });
        }
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
