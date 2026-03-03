@extends('field.layouts.app')

@section('title', 'Dashboard' )
@section('page-title', 'Dashboard')

@section('content')
@php
    $isForeman = $mode === 'foreman';
    $isWorker = $mode === 'worker';
@endphp

<div class="page-title">
    <i class="bi bi-speedometer2"></i>
    {{ $isForeman ? 'Foreman Dashboard' : 'Worker Dashboard' }}
</div>
<p class="page-subtitle">{{ $isForeman ? 'Manage your site workforce and approvals' : 'Track your attendance and earnings' }}</p>

@if($isWorker)
    <div class="kpi-cards">
        <div class="card kpi-card primary">
            <div class="card-body">
                <div class="kpi-label">Available Balance</div>
                <div class="kpi-value">KES {{ number_format($availableBalance, 2) }}</div>
            </div>
        </div>
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-label">Last Payout</div>
                <div class="kpi-value">{{ $lastPayout ? 'KES '.number_format($lastPayout->net_amount, 2) : '—' }}</div>
                <div class="kpi-meta">{{ $lastPayout && $lastPayout->paid_at ? $lastPayout->paid_at->format('M d, Y') : 'No payout yet' }}</div>
            </div>
        </div>
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-label">Pending Withdrawals</div>
                <div class="kpi-value">{{ count($pendingClaims) }}</div>
                <div class="kpi-meta">{{ number_format($pendingClaims->sum('requested_amount'), 2) }} KES total</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="form-section h-100">
                <div class="form-section-title">
                    <i class="bi bi-building"></i>
                    My Active Sites
                </div>
                @php
                    $activeSites = auth()->user()->siteWorkers()->whereNull('ended_at')->with('site')->get();
                @endphp
                @if($activeSites->count() > 0)
                    @foreach($activeSites as $assignment)
                        @php
                            $site = $assignment->site;
                            $siteWindowInfo = $withdrawalBalancesBySite[$site->id] ?? null;
                        @endphp
                        <div class="card mb-3 border">
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">{{ $site->name }}</h6>
                                <div class="small text-muted mb-2">
                                    <i class="bi bi-geo-alt"></i> {{ $site->location }}
                                </div>
                                
                                @if($siteWindowInfo)
                                    <div class="border-top pt-2 mt-2">
                                        <div class="small">
                                            <strong>Available Balance:</strong>
                                        </div>
                                        @if($siteWindowInfo['total_available_anytime'] > 0)
                                            <div class="text-success">
                                                <i class="bi bi-cash-stack"></i> KES {{ number_format($siteWindowInfo['total_available_anytime'], 2) }}
                                                <small class="text-muted">(Anytime)</small>
                                            </div>
                                        @endif
                                        
                                        @if($siteWindowInfo['current_cycle'] && $siteWindowInfo['current_cycle']['cycle'])
                                            @php
                                                $currentCycle = $siteWindowInfo['current_cycle'];
                                                $isInWindow = $currentCycle['in_window'] ?? false;
                                            @endphp
                                            <div class="{{ $isInWindow ? 'text-success' : 'text-warning' }}">
                                                <i class="bi bi-{{ $isInWindow ? 'check-circle' : 'clock' }}"></i> 
                                                KES {{ number_format($currentCycle['balance'], 2) }}
                                                <small class="text-muted">(Current Cycle - {{ $isInWindow ? 'Open' : 'Closed' }})</small>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <div class="border-top pt-2 mt-2">
                                    <div class="small text-muted">
                                        <strong>Role:</strong> {{ $assignment->is_foreman ? 'Foreman' : 'Worker' }}
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Started:</strong> {{ $assignment->started_at ? $assignment->started_at->format('M d, Y') : 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="mt-3">
                        <a href="{{ route('field.claims') }}" class="btn btn-primary w-100">
                            <i class="bi bi-wallet2"></i> Go to Withdrawal Page
                        </a>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-building" style="font-size: 2rem;"></i>
                        <p class="mt-2">No active site assignments</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="form-section h-100">
                <div class="form-section-title">
                    <i class="bi bi-hourglass-split"></i>
                    Recent Withdrawal Status
                </div>
                <div class="table-responsive">
                    <table class="table table-section mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingClaims->take(5) as $claim)
                                <tr>
                                    <td>{{ $claim->created_at?->format('M d, Y') }}</td>
                                    <td class="fw-semibold">KES {{ number_format($claim->requested_amount, 2) }}</td>
                                    <td>
                                        @if($claim->status === 'pending_foreman')
                                            <span class="badge bg-warning">Awaiting Foreman</span>
                                        @elseif($claim->status === 'pending_owner')
                                            <span class="badge bg-info">Awaiting Owner</span>
                                        @elseif($claim->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($claim->status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($claim->status === 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No withdrawal requests yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@else
    <div class="kpi-cards">
        <div class="card kpi-card primary">
            <div class="card-body">
                <div class="kpi-label">Low Stock Alerts</div>
                <div class="kpi-value">{{ $inventorySummary['low_stock'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-label">Pending Procurement Requests</div>
                <div class="kpi-value">{{ $inventorySummary['pending_requests'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-label">Progress Logs Today</div>
                <div class="kpi-value">{{ $inventorySummary['progress_today'] ?? 0 }}</div>
                <a href="{{ route('field.inventory.index') }}" class="btn btn-sm btn-outline-secondary mt-2">Open Inventory</a>
            </div>
        </div>
    </div>

    <div class="form-section">
                <div class="form-section-title">
                    <i class="bi bi-calendar-event"></i>
                    Mark Attendance
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Site</label>
                        <select class="form-select" id="siteSelector" required>
                            <option value="">Select site...</option>
                            @php
                                $foremanSites = [];
                                foreach(auth()->user()->siteWorkers as $sw) {
                                    if($sw->is_foreman && !$sw->ended_at) $foremanSites[] = $sw->site;
                                }
                                foreach(auth()->user()->siteMembers as $sm) {
                                    if($sm->role === 'foreman') $foremanSites[] = $sm->site;
                                }
                            @endphp
                            @foreach($foremanSites as $site)
                                <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendanceDate" value="{{ old('attendance_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="loadRosterBtn">
                            <i class="bi bi-arrow-clockwise"></i> Load Roster
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('foreman.attendance.bulk') }}" id="bulkAttendanceForm" class="d-none">
                    @csrf
                    <input type="hidden" name="site_id" id="bulkSiteId">
                    <input type="hidden" name="attendance_date" id="bulkAttendanceDate">

                    <div class="table-responsive table-section" id="rosterTableContainer">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Hours</th>
                                </tr>
                            </thead>
                            <tbody id="rosterTableBody">
                                <tr><td colspan="4" class="text-center text-muted py-4">Select a site and date to load roster</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-none" id="submissionArea">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Save Attendance
                        </button>
                    </div>
                </form>
    </div>

    <div class="form-section">
                <div class="form-section-title">
                    <i class="bi bi-file-earmark-check"></i>
                    Pending Worker Withdrawals (Foreman Approval)
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Site</label>
                        <select class="form-select" id="claimsSiteSelector" required>
                            <option value="">Select site...</option>
                            @foreach($foremanSites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="loadClaimsBtn">
                            <i class="bi bi-arrow-clockwise"></i> Load Claims
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('foreman.claims.bulk-action') }}" id="bulkClaimsForm" class="d-none">
                    @csrf
                    <input type="hidden" name="site_id" id="claimsSiteId">
                    <input type="hidden" name="action" id="claimsAction">

                    <div class="table-responsive table-section" id="claimsTableContainer">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="form-check-input" id="selectAllClaims">
                                    </th>
                                    <th>Worker</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                </tr>
                            </thead>
                            <tbody id="claimsTableBody">
                                <tr><td colspan="5" class="text-center text-muted py-4">Select a site to load claims</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-none" id="claimsSubmissionArea">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success w-100" onclick="document.getElementById('claimsAction').value='approve'; document.getElementById('bulkClaimsForm').submit();">
                                    <i class="bi bi-check-circle"></i> Approve Selected
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-danger w-100" onclick="document.getElementById('claimsAction').value='reject'; document.getElementById('bulkClaimsForm').submit();">
                                    <i class="bi bi-x-circle"></i> Reject Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
    </div>

    <script>
        // Roster loading
        document.getElementById('loadRosterBtn').addEventListener('click', function() {
            const siteId = document.getElementById('siteSelector').value;
            const date = document.getElementById('attendanceDate').value;

            if (!siteId || !date) {
                alert('Please select site and date');
                return;
            }

            // Set hidden inputs
            document.getElementById('bulkSiteId').value = siteId;
            document.getElementById('bulkAttendanceDate').value = date;

            // Show form
            document.getElementById('bulkAttendanceForm').classList.remove('d-none');
            document.getElementById('submissionArea').classList.remove('d-none');

            // Build roster table (placeholder - replace with API call in production)
            const tbody = document.getElementById('rosterTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        Roster data would load from API endpoint
                    </td>
                </tr>
            `;
        });

        // Claims loading
        document.getElementById('loadClaimsBtn').addEventListener('click', function() {
            const siteId = document.getElementById('claimsSiteSelector').value;

            if (!siteId) {
                alert('Please select a site');
                return;
            }

            document.getElementById('claimsSiteId').value = siteId;
            document.getElementById('bulkClaimsForm').classList.remove('d-none');
            document.getElementById('claimsSubmissionArea').classList.remove('d-none');

            // Build claims table (placeholder - replace with API call in production)
            const tbody = document.getElementById('claimsTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        Claims data would load from API endpoint
                    </td>
                </tr>
            `;
        });

        // Select all claims checkbox
        document.getElementById('selectAllClaims').addEventListener('change', function() {
            document.querySelectorAll('.claim-checkbox').forEach(cb => cb.checked = this.checked);
        });
    </script>

@endif
@endsection
