<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>@yield('title', 'SiteGrid')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-indigo: #1e1b4b;
            --accent-orange: #f97316;
            --accent-orange-dark: #ea580c;
            --danger: #ef4444;
            --success: #22c55e;
        }

        body {
            background-color: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-indigo) 0%, #312e81 100%);
            min-height: 100vh;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--accent-orange);
        }

        .sidebar a.active {
            background: rgba(249, 115, 22, 0.18);
            color: white;
            border-left-color: var(--accent-orange);
        }

        .logo {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-weight: 700;
            font-size: 24px;
            text-align: center;
        }

        .nav-section {
            padding: 15px 0;
        }

        .nav-section-title {
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 10px 20px;
            letter-spacing: 0.5px;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-orange);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .btn-logout {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        .content-area {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .card-body {
            padding: 20px;
        }

        .metric-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--accent-orange);
        }

        .sidebar-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--primary-indigo);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem;
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

        .metric-label {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }

        .alert {
            border: none;
            border-radius: 8px;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        table {
            margin-bottom: 0;
        }

        table thead th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        table tbody td {
            border-color: #e5e7eb;
            padding: 15px;
            vertical-align: middle;
        }

        table tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0;
                width: 60vw;
                max-width: 320px;
                min-width: 240px;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.is-open {
                transform: translateX(0);
            }

            .sidebar-overlay.is-open {
                opacity: 1;
                pointer-events: auto;
            }

        .impersonation-banner {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-box {
            position: relative;
            width: 400px;
            max-width: 100%;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-results.show {
            display: block;
        }

        .search-result-item {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-result-item:hover {
            background: #f9fafb;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-category {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6b7280;
            padding: 8px 12px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
            }
        }
    </style>
    @yield('style')
</head>
<body>
    <!-- Impersonation Banner -->
    @if(session('impersonate_from'))
        <div class="impersonation-banner">
            <div>
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Impersonating:</strong> {{ auth()->user()->name }} ({{ auth()->user()->role }})
            </div>
            <form method="POST" action="{{ route('admin.impersonate.leave') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Stop Impersonation
                </button>
            </form>
        </div>
    @endif

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar col-md-3 col-lg-2" id="adminSidebar">
            <div class="logo">SiteGrid Admin</div>
            
            <div class="nav-section">
                <div class="nav-section-title">Menu</div>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.sites.index') }}" class="{{ request()->routeIs('admin.sites.*') ? 'active' : '' }}">
                    <i class="bi bi-building"></i>
                    Sites
                </a>
                <a href="{{ route('admin.workers.index') }}" class="{{ request()->routeIs('admin.workers.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    Workers
                </a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i>
                    Users
                </a>
                <a href="{{ route('admin.attendance.index') }}" class="{{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i>
                    Attendance
                </a>
                <a href="{{ route('admin.paycycles.index') }}" class="{{ request()->routeIs('admin.paycycles.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-week"></i>
                    Pay Cycles
                </a>
                <a href="{{ route('admin.payouts.index') }}" class="{{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin"></i>
                    Payouts
                </a>
                <a href="{{ route('admin.escrow.index') }}" class="{{ request()->routeIs('admin.escrow.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i>
                    Escrow
                </a>
                <a href="{{ route('admin.financial.dashboard') }}" class="{{ request()->routeIs('admin.financial.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up"></i>
                    Financial Reports
                </a>
                <a href="{{ route('admin.inventory.index') }}" class="{{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i>
                    Inventory
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Management</div>
                <a href="{{ route('admin.invoices.index') }}" class="{{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i>
                    Invoices
                </a>
                <a href="{{ route('admin.kyc.pending') }}" class="{{ request()->routeIs('admin.kyc.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-check"></i>
                    KYC
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Monitoring</div>
                <a href="{{ route('admin.webhooks.index') }}" class="{{ request()->routeIs('admin.webhooks.*') ? 'active' : '' }}">
                    <i class="bi bi-lightning"></i>
                    Webhooks
                </a>
                <a href="{{ route('admin.integration-health.index') }}" class="{{ request()->routeIs('admin.integration-health.*') ? 'active' : '' }}">
                    <i class="bi bi-heart-pulse"></i>
                    Health
                </a>
                <a href="{{ route('admin.activity.index') }}" class="{{ request()->routeIs('admin.activity.*') ? 'active' : '' }}">
                    <i class="bi bi-activity"></i>
                    Activity Feed
                </a>
                <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i>
                    Audit Logs
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i>
                    Settings
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="sidebar-toggle d-md-none" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="topbar-title d-none d-sm-block">@yield('page-title', 'Dashboard')</div>
                </div>

                <!-- Global Search -->
                <div class="search-box d-none d-lg-block">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0" 
                               id="globalSearch" 
                               placeholder="Search sites, users, payouts..."
                               autocomplete="off">
                    </div>
                    <div class="search-results" id="searchResults"></div>
                </div>

                <div class="user-menu">
                    <div class="user-avatar">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mb-0" id="logoutForm">
                        @csrf
                        <button type="button" data-bs-toggle="modal" data-bs-target="#logoutModal" class="btn-logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
            </div>

            <!-- Content -->
            <div class="content-area">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Error:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
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
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            if (!sidebar) {
                return;
            }
            sidebar.classList.toggle('is-open');
            overlay.classList.toggle('is-open');
        }

        if (toggleBtn && overlay) {
            toggleBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        }

        // Global Search
        const searchInput = document.getElementById('globalSearch');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    searchResults.classList.remove('show');
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`{{ route('admin.search') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            displaySearchResults(data);
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                        });
                }, 300);
            });

            // Hide results on click outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.remove('show');
                }
            });
        }

        function displaySearchResults(data) {
            let html = '';
            let hasResults = false;

            ['sites', 'users', 'payouts', 'invoices'].forEach(category => {
                if (data[category] && data[category].length > 0) {
                    hasResults = true;
                    html += `<div class="search-category">${category.toUpperCase()}</div>`;
                    data[category].forEach(item => {
                        html += `
                            <a href="${item.url}" class="search-result-item text-decoration-none d-block">
                                <div class="d-flex align-items-center">
                                    <i class="bi ${item.icon} me-2 text-primary"></i>
                                    <div>
                                        <div class="fw-semibold text-dark">${item.title}</div>
                                        <small class="text-muted">${item.subtitle}</small>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                }
            });

            if (!hasResults) {
                html = '<div class="p-3 text-center text-muted">No results found</div>';
            }

            searchResults.innerHTML = html;
            searchResults.classList.add('show');
        }
    </script>
    @yield('script')
</body>
</html>
