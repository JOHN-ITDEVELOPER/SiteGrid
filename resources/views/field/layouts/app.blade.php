<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>@yield('title', 'Dashboard') - SiteGrid</title>
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

        .field-sidebar {
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

        .field-brand-box {
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0.5rem 0.75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            margin-bottom: 0.75rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
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

        .field-sidebar a {
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

        .field-sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left-color: var(--brand-orange);
        }

        .field-sidebar a.active {
            background: rgba(249, 115, 22, 0.18);
            color: #fff;
            border-left-color: var(--brand-orange);
        }

        .field-main {
            margin-left: 250px;
            min-height: 100vh;
        }

        .field-topbar {
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

        .field-topbar-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--brand-indigo);
        }

        .field-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .field-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            background: #f3f4f6;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--brand-indigo);
        }

        .field-content {
            padding: 1.5rem;
        }

        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--brand-indigo);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .kpi-card {
            border: 0;
            border-radius: 0.9rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            background: #fff;
        }

        .kpi-card.primary {
            border-top: 4px solid var(--brand-orange);
        }

        .kpi-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .kpi-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--brand-indigo);
        }

        .kpi-meta {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 0.5rem;
        }

        .form-section {
            background: #fff;
            border-radius: 0.9rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--brand-indigo);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-section {
            background: #fff;
            border-radius: 0.9rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-section thead {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-section th {
            color: #6b7280;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
        }

        .table-section td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .btn-primary {
            background: var(--brand-indigo);
            border: none;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #17153d;
        }

        .btn-outline-secondary {
            color: var(--brand-indigo);
            border-color: var(--brand-indigo);
        }

        .btn-outline-secondary:hover {
            background: var(--brand-indigo);
            color: #fff;
        }

        .alert {
            border: none;
            border-radius: 0.9rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .form-control, .form-select {
            border: 1px solid #e5e7eb;
            border-radius: 0.6rem;
            padding: 0.6rem 0.875rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--brand-orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .hamburger-btn {
            display: none;
            border: none;
            background: none;
            font-size: 1.5rem;
            color: var(--brand-indigo);
            cursor: pointer;
            padding: 0.5rem;
            margin-right: 1rem;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.is-visible {
            display: block;
            opacity: 1;
        }

        @media (max-width: 768px) {
            .hamburger-btn {
                display: block;
            }

            .field-sidebar {
                position: fixed;
                left: 0;
                width: 60vw;
                max-width: 320px;
                min-width: 240px;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .field-sidebar.is-open {
                transform: translateX(0);
            }

            .field-main {
                margin-left: 0;
            }

            .field-topbar-title {
                font-size: 1rem;
            }

            .field-role-badge {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="field-sidebar">
        <div class="field-brand-box">
            <i class="bi bi-briefcase"></i> SiteGrid
        </div>

        <!-- WORKER SECTION (Visible to all) -->
        <div class="nav-section">
            <div class="nav-section-title">Worker</div>
            <a href="{{ route('field.dashboard') }}" class="{{ request()->routeIs('field.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('field.claims') }}" class="{{ request()->routeIs('field.claims*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> My Withdrawals
            </a>
            <a href="{{ route('field.attendance') }}" class="{{ request()->routeIs('field.attendance') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i> Attendance
            </a>
            <a href="{{ route('field.payhistory') }}" class="{{ request()->routeIs('field.payhistory') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Pay History
            </a>
        </div>

        <!-- FOREMAN SECTION (Visible only to foremen) -->
        @if(auth()->user()->siteWorkers()->where('is_foreman', true)->exists() || auth()->user()->siteMembers()->where('role', 'foreman')->exists())
            <div class="nav-section">
                <div class="nav-section-title">Foreman</div>
                <a href="{{ route('field.roster') }}" class="{{ request()->routeIs('field.roster') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i> Roster
                </a>
                <a href="{{ route('field.claims-approval') }}" class="{{ request()->routeIs('field.claims-approval') ? 'active' : '' }}">
                    <i class="bi bi-check-circle"></i> Approve Claims
                </a>
                <a href="{{ route('field.add-worker') }}" class="{{ request()->routeIs('field.add-worker') ? 'active' : '' }}">
                    <i class="bi bi-person-plus"></i> Add Worker
                </a>
                <a href="{{ route('field.inventory.index') }}" class="{{ request()->routeIs('field.inventory.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i> Inventory
                </a>
            </div>
        @endif

        <!-- SETTINGS SECTION -->
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="{{ route('field.settings') }}" class="{{ request()->routeIs('field.settings') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Settings
            </a>
            <form method="POST" action="{{ route('logout') }}" class="m-0" id="logoutForm">
                @csrf
                <button type="button" data-bs-toggle="modal" data-bs-target="#logoutModal" class="btn btn-link text-decoration-none w-100 text-start" style="color: rgba(255, 255, 255, 0.85); padding: 0.65rem 0.75rem; margin-bottom: 0.25rem;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- MAIN CONTENT -->
    <div class="field-main">
        <!-- TOPBAR -->
        <div class="field-topbar">
            <div class="d-flex align-items-center">
                <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div class="field-topbar-title">@yield('page-title', 'Dashboard')</div>
            </div>
            <div class="field-user-info">
                <div class="field-role-badge">
                    <i class="bi bi-{{ auth()->user()->siteWorkers()->where('is_foreman', true)->exists() || auth()->user()->siteMembers()->where('role', 'foreman')->exists() ? 'person-check' : 'person' }}"></i>
                    {{ auth()->user()->siteWorkers()->where('is_foreman', true)->exists() || auth()->user()->siteMembers()->where('role', 'foreman')->exists() ? 'Foreman' : 'Worker' }}
                </div>
                <span class="text-muted small">{{ auth()->user()->name }}</span>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="field-content">
            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-circle me-2 flex-shrink-0 mt-1"></i>
                        <div>
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

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
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.field-sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('is-open');
            overlay.classList.toggle('is-visible');
        }

        // Close sidebar when clicking a link on mobile
        document.querySelectorAll('.field-sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });
    </script>
</body>
</html>
