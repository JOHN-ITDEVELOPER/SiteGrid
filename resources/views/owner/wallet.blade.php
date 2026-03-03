@extends('owner.layouts.app')

@section('title', 'Wallet')
@section('page-title', 'Wallet & Top-up')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Current Balance</div>
            <div class="h3 mb-0 text-success">KES {{ number_format($wallet->balance ?? 0, 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Pending Payouts</div>
            <div class="h3 mb-0 text-warning">KES {{ number_format($pendingPayoutAmount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Available</div>
            <div class="h3 mb-0">KES {{ number_format(($wallet->balance ?? 0) - $pendingPayoutAmount, 2) }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0">Top-up Wallet</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('owner.wallet.topup') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (KES) *</label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" min="1" max="300000" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Min: KES 1 | Max: KES 300,000</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">M-Pesa Phone Number *</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="254712345678" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Format: 254712345678 (no + or spaces)</small>
                    </div>

                    <div class="alert alert-light border small">
                        <strong>How it works:</strong>
                        <ol class="mb-0 mt-1">
                            <li>Enter amount and confirm</li>
                            <li>You'll receive an M-Pesa STK push prompt</li>
                            <li>Enter your M-Pesa PIN to complete</li>
                            <li>Funds reflect in 1-5 minutes</li>
                        </ol>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="topupBtn">
                        <span class="btn-text">Initiate Top-up</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="loading-text d-none">Sending STK Push...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0">Recent Top-ups</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topups as $topup)
                            <tr>
                                <td>{{ $topup->created_at->format('d M Y H:i') }}</td>
                                <td class="text-success">+KES {{ number_format($topup->amount, 2) }}</td>
                                <td><span class="badge text-bg-success">Completed</span></td>
                                <td><small class="text-muted">{{ $topup->description }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No top-up history yet. Click "Initiate Top-up" to add funds.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="card kpi-card">
        <div class="card-header bg-white border-0">
            <h6 class="mb-0">All Wallet Transactions</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance After</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $transaction->description }}</td>
                            <td>
                                @if($transaction->type === 'credit')
                                    <span class="badge text-bg-success">Credit</span>
                                @else
                                    <span class="badge text-bg-danger">Debit</span>
                                @endif
                            </td>
                            <td class="text-end {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->type === 'credit' ? '+' : '-' }}KES {{ number_format($transaction->amount, 2) }}
                            </td>
                            <td class="text-end"><strong>KES {{ number_format($transaction->balance_after, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="card kpi-card">
        <div class="card-header bg-white border-0">
            <h6 class="mb-0">Your Sites & Funding Status</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Site Name</th>
                        <th>Active Workers</th>
                        <th>Weekly Est. Payroll</th>
                        <th>Funding Method</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sites as $site)
                        <tr>
                            <td>{{ $site->name }}</td>
                            <td>{{ $site->workers()->whereNull('ended_at')->count() }}</td>
                            <td>KES {{ number_format($site->workers()->whereNull('ended_at')->sum('weekly_rate'), 2) }}</td>
                            <td><span class="badge text-bg-light border">{{ ucwords(str_replace('_', ' ', $site->payout_method ?? 'platform_managed')) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No sites found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const topupForm = document.querySelector('form[action="{{ route('owner.wallet.topup') }}"]');
    const topupBtn = document.getElementById('topupBtn');
    let statusCheckInterval = null;
    let checkoutRequestId = null;
    
    if (topupForm && topupBtn) {
        topupForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            // Disable button and show loading state
            topupBtn.disabled = true;
            topupBtn.querySelector('.btn-text').classList.add('d-none');
            topupBtn.querySelector('.spinner-border').classList.remove('d-none');
            topupBtn.querySelector('.loading-text').classList.remove('d-none');
            
            // Submit form via AJAX
            const formData = new FormData(topupForm);
            
            fetch(topupForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || formData.get('_token'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    checkoutRequestId = data.checkout_request_id;
                    showProcessingModal();
                    startStatusPolling();
                } else {
                    showErrorModal(data.message || 'Failed to initiate payment');
                    resetButton();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('An error occurred. Please try again.');
                resetButton();
            });
        });
    }
    
    function showProcessingModal() {
        // Create modal overlay
        const modalHtml = `
            <div class="modal fade show" id="stkProcessingModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center py-5" id="modalContent">
                            <div class="mb-4">
                                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <h5 class="mb-3" id="modalTitle">Waiting for Payment...</h5>
                            <p class="text-muted mb-4" id="modalMessage">Check your phone for the M-Pesa prompt</p>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                            </div>
                            <div class="mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-phone"></i> Enter your M-Pesa PIN to complete
                                </small>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('stkProcessingModal').remove(); window.location.reload();">
                                    Cancel & Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    function startStatusPolling() {
        let attempts = 0;
        const maxAttempts = 60; // Poll for 60 seconds (60 attempts * 1 second)
        
        statusCheckInterval = setInterval(() => {
            attempts++;
            
            if (attempts > maxAttempts) {
                clearInterval(statusCheckInterval);
                showTimeoutModal();
                return;
            }
            
            checkTransactionStatus();
        }, 1000); // Check every 1 second
    }
    
    function checkTransactionStatus() {
        if (!checkoutRequestId) return;
        
        fetch(`{{ url('owner/wallet/transaction') }}/${checkoutRequestId}/status`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    clearInterval(statusCheckInterval);
                    showSuccessModal(data.message, data.receipt);
                } else if (data.status === 'failed') {
                    clearInterval(statusCheckInterval);
                    showFailedModal(data.message);
                }
                // If still pending, keep polling
            })
            .catch(error => {
                console.error('Status check error:', error);
            });
    }
    
    function showSuccessModal(message, receipt) {
        const modalContent = document.getElementById('modalContent');
        if (modalContent) {
            modalContent.innerHTML = `
                <div class="mb-4">
                    <div class="text-success" style="font-size: 4rem;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <h5 class="mb-3 text-success">Payment Successful!</h5>
                <p class="text-muted mb-4">${message}</p>
                ${receipt ? `<p class="small text-muted">Receipt: ${receipt}</p>` : ''}
                <button type="button" class="btn btn-success" onclick="window.location.reload();">
                    View Updated Balance
                </button>
            `;
        }
    }
    
    function showFailedModal(message) {
        const modalContent = document.getElementById('modalContent');
        if (modalContent) {
            modalContent.innerHTML = `
                <div class="mb-4">
                    <div class="text-danger" style="font-size: 4rem;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <h5 class="mb-3 text-danger">Payment Failed</h5>
                <p class="text-muted mb-4">${message}</p>
                <div class="alert alert-light border small text-start">
                    <strong>Common reasons:</strong>
                    <ul class="mb-0 mt-2">
                        <li>You cancelled the payment</li>
                        <li>Incorrect M-Pesa PIN</li>
                        <li>Insufficient M-Pesa balance</li>
                        <li>Request timed out</li>
                    </ul>
                </div>
                <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                    Try Again
                </button>
            `;
        }
        resetButton();
    }
    
    function showTimeoutModal() {
        const modalContent = document.getElementById('modalContent');
        if (modalContent) {
            modalContent.innerHTML = `
                <div class="mb-4">
                    <div class="text-warning" style="font-size: 4rem;">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                </div>
                <h5 class="mb-3 text-warning">Request Timed Out</h5>
                <p class="text-muted mb-4">We're still waiting for payment confirmation. Please check your phone or try again.</p>
                <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                    Refresh & Check Balance
                </button>
            `;
        }
        resetButton();
    }
    
    function showErrorModal(message) {
        alert(message);
    }
    
    function resetButton() {
        if (topupBtn) {
            topupBtn.disabled = false;
            topupBtn.querySelector('.btn-text').classList.remove('d-none');
            topupBtn.querySelector('.spinner-border').classList.add('d-none');
            topupBtn.querySelector('.loading-text').classList.add('d-none');
        }
    }
});
</script>
@endpush
