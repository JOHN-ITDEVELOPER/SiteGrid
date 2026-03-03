@extends('field.layouts.app')

@section('title', 'My Withdrawals')
@section('page-title', 'My Withdrawals')

@section('content')
<div class="page-title">
    <i class="bi bi-wallet2"></i>
    My Withdrawals
</div>
<p class="page-subtitle">Request and manage your earned pay withdrawals</p>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-plus-circle"></i>
        Request New Withdrawal
    </div>
    <div class="mb-3">
        <span class="badge bg-primary">Available Balance: KES {{ number_format($availableBalance ?? 0, 2) }}</span>
    </div>
    @if(($availableBalance ?? 0) <= 0)
        <div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle"></i>
            You cannot submit a withdrawal request with zero balance.
        </div>
    @endif

    <!-- Payout Window Information -->
    @if($siteWindows && count($siteWindows) > 0)
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle"></i>
            <strong>Payout Window Schedule:</strong> You can only request withdrawals during your site's designated window hours.
        </div>
        <div class="mb-3">
            <div class="row g-2">
                @foreach($siteWindows as $siteId => $windowInfo)
                    <div class="col-md-6">
                        <div class="card border {{ $windowInfo['is_within_window'] ? 'border-success' : 'border-warning' }}">
                            <div class="card-body p-3">
                                <div class="fw-semibold">{{ $windowInfo['site']->name }}</div>
                                @php
                                    $settings = $windowInfo['settings'];
                                    $windows = $settings['windows'] ?? [];
                                    $firstWindow = $windows[0] ?? null;
                                @endphp
                                @if($firstWindow)
                                    <div class="small text-muted mt-2">
                                        <div><i class="bi bi-calendar"></i> Days: {{ implode(', ', $firstWindow['days'] ?? []) }}</div>
                                        <div><i class="bi bi-clock"></i> Time: {{ substr($firstWindow['time'] ?? '17:00', 0, 5) }} onwards</div>
                                    </div>
                                    <div class="mt-2">
                                        @if($windowInfo['is_within_window'])
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Open Now
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-clock"></i> {{ $windowInfo['window_message'] }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="small text-muted mt-2">No window configured</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('field.claims.store') }}" class="row g-3" id="withdrawalForm">
        @csrf
        <fieldset {{ ($availableBalance ?? 0) <= 0 ? 'disabled' : '' }} id="withdrawalFieldset">
        <div class="col-12">
            <label class="form-label">Select Site</label>
            <select class="form-select @error('site_id') is-invalid @enderror" name="site_id" id="siteSelect" required>
                <option value="">Choose a site...</option>
                @foreach(auth()->user()->siteWorkers()->whereNull('ended_at')->with('site')->get() as $assignment)
                    <option value="{{ $assignment->site_id }}" {{ old('site_id') == $assignment->site_id ? 'selected' : '' }}>
                        {{ $assignment->site?->name }}
                    </option>
                @endforeach
            </select>
            @error('site_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            
            <!-- Site Window Status -->
            <div id="siteWindowStatus" class="mt-2"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Amount to Withdraw (KES)</label>
            <input type="number" class="form-control @error('requested_amount') is-invalid @enderror" name="requested_amount" min="1" step="0.01" value="{{ old('requested_amount') }}" required>
            @error('requested_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Reason (Optional)</label>
            <input type="text" class="form-control" name="reason" maxlength="500" value="{{ old('reason') }}" placeholder="e.g., Weekly earnings">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                <i class="bi bi-check-lg"></i> Submit Withdrawal Request
            </button>
        </div>
        </fieldset>
    </form>

    <!-- JavaScript for dynamic window checking -->
    <script>
        const siteWindows = @json($siteWindows);
        const siteSelect = document.getElementById('siteSelect');
        const siteWindowStatus = document.getElementById('siteWindowStatus');
        const withdrawalFieldset = document.getElementById('withdrawalFieldset');
        const submitBtn = document.getElementById('submitBtn');

        function updateWindowStatus() {
            const selectedSiteId = parseInt(siteSelect.value);
            
            if (!selectedSiteId || !siteWindows[selectedSiteId]) {
                siteWindowStatus.innerHTML = '';
                withdrawalFieldset.disabled = false;
                submitBtn.disabled = false;
                return;
            }

            const windowInfo = siteWindows[selectedSiteId];
            const isOpen = windowInfo.is_within_window;
            
            if (isOpen) {
                siteWindowStatus.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle"></i> Withdrawal window is <strong>OPEN</strong>. You can submit your request now.
                    </div>
                `;
                withdrawalFieldset.disabled = false;
                submitBtn.disabled = false;
            } else {
                siteWindowStatus.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Withdrawal window is CLOSED.</strong><br>
                        ${windowInfo.window_message}<br>
                        <small>You cannot submit withdrawal requests outside the designated window.</small>
                    </div>
                `;
                withdrawalFieldset.disabled = true;
                submitBtn.disabled = true;
            }
        }

        siteSelect.addEventListener('change', updateWindowStatus);
        
        // Initial check if site was pre-selected
        if (siteSelect.value) {
            updateWindowStatus();
        }
    </script>
</div>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-hourglass-split"></i>
        All Withdrawal Requests
    </div>
    @if(count($claims) > 0)
        <div class="table-responsive">
            <table class="table table-section mb-0">
                <thead>
                    <tr>
                        <th>Requested</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($claims as $claim)
                        <tr>
                            <td>{{ $claim->created_at->format('M d, Y H:i') }}</td>
                            <td class="fw-semibold">KES {{ number_format($claim->requested_amount, 2) }}</td>
                            <td>{{ substr($claim->reason ?? '—', 0, 50) }}{{ strlen($claim->reason ?? '') > 50 ? '...' : '' }}</td>
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
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $claims->links() }}
        </div>
    @else
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2">No withdrawal requests yet</p>
        </div>
    @endif
</div>
@endsection
