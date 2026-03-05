@extends('owner.layouts.app')

@section('title', 'Invoices')
@section('page-title', 'Invoices & Billing')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="small text-muted">Unpaid</div><div class="h5 mb-0">KES {{ number_format($summary['unpaid'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="small text-muted">Overdue</div><div class="h5 mb-0 text-danger">KES {{ number_format($summary['overdue'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="small text-muted">Paid</div><div class="h5 mb-0 text-success">KES {{ number_format($summary['paid'], 2) }}</div></div></div>
</div>

<div class="card kpi-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('owner.invoices') }}">
            <div class="col-md-5">
                <select class="form-select" name="site_id">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ (string)$siteId === (string)$site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    @foreach(['unpaid','overdue','paid'] as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<div class="card kpi-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Period</th>
                    <th>Workers</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->site->name ?? 'Unknown Site' }}</td>
                        <td>{{ $invoice->period_start?->format('d M') }} - {{ $invoice->period_end?->format('d M Y') }}</td>
                        <td>{{ $invoice->worker_count }}</td>
                        <td>KES {{ number_format($invoice->amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $invoice->status === 'paid' ? 'text-bg-success' : ($invoice->status === 'overdue' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td>
                            @if($invoice->due_date)
                                <span class="{{ now()->toDateString() > $invoice->due_date->toDateString() ? 'text-danger fw-semibold' : '' }}">
                                    {{ $invoice->due_date->format('d M Y') }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $invoice->created_at->format('d M Y') }}</td>
                        <td>
                            @if($invoice->status !== 'paid' && $invoice->payment_method === 'manual_mpesa')
                                <button type="button" class="btn btn-sm btn-outline-success stk-payment-btn" data-invoice-id="{{ $invoice->id }}" data-invoice-amount="{{ $invoice->amount }}" data-invoice-period="{{ $invoice->period_start?->format('d M') }} - {{ $invoice->period_end?->format('d M Y') }}">
                                    <i class="bi bi-phone"></i> Pay Now (STK Push)
                                </button>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">No invoices found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $invoices->links() }}</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stkButtons = document.querySelectorAll('.stk-payment-btn');
    let statusCheckInterval = null;
    let currentInvoiceId = null;
    let checkoutRequestId = null;
    
    stkButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const invoiceId = this.dataset.invoiceId;
            const amount = this.dataset.invoiceAmount;
            const period = this.dataset.invoicePeriod;
            
            currentInvoiceId = invoiceId;
            showPaymentModal(amount, period);
            initiatePayment(invoiceId);
        });
    });
    
    function showPaymentModal(amount, period) {
        const modalHtml = `
            <div class="modal fade show" id="stkInvoiceModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light border-0">
                            <h5 class="modal-title">Invoice Payment - STK Push</h5>
                            <button type="button" class="btn-close" onclick="closeModal()"></button>
                        </div>
                        <div class="modal-body text-center py-5" id="modalContent">
                            <div class="mb-3">
                                <div class="h4 text-primary">KES ${amount}</div>
                                <div class="small text-muted">Period: ${period}</div>
                            </div>
                            <div class="mb-4">
                                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <h5 class="mb-3" id="modalTitle">Initiating Payment...</h5>
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
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeModal();">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    function initiatePayment(invoiceId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        fetch(`{{ url('owner/invoices') }}/${invoiceId}/retry-payment`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                checkoutRequestId = data.checkout_request_id;
                startStatusPolling();
            } else {
                showPaymentError(data.message || 'Failed to initiate payment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showPaymentError('An error occurred. Please try again.');
        });
    }
    
    function startStatusPolling() {
        let attempts = 0;
        const maxAttempts = 60;
        
        statusCheckInterval = setInterval(() => {
            attempts++;
            
            if (attempts > maxAttempts) {
                clearInterval(statusCheckInterval);
                showPaymentTimeout();
                return;
            }
            
            checkPaymentStatus();
        }, 1000);
    }
    
    function checkPaymentStatus() {
        if (!checkoutRequestId) return;
        
        fetch(`{{ url('owner/wallet/transaction') }}/${checkoutRequestId}/status`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    clearInterval(statusCheckInterval);
                    showPaymentSuccess();
                } else if (data.status === 'failed') {
                    clearInterval(statusCheckInterval);
                    showPaymentFailed(data.message || 'Payment was not completed');
                }
            })
            .catch(error => console.error('Status check error:', error));
    }
    
    function showPaymentSuccess() {
        const modalContent = document.getElementById('modalContent');
        if (modalContent) {
            modalContent.innerHTML = `
                <div class="mb-4">
                    <div class="text-success" style="font-size: 4rem;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <h5 class="mb-3 text-success">Payment Successful!</h5>
                <p class="text-muted mb-4">Your invoice has been paid successfully. Thank you!</p>
                <button type="button" class="btn btn-success" onclick="window.location.reload();">
                    View Updated Invoice
                </button>
            `;
        }
    }
    
    function showPaymentFailed(message) {
        const modalContent = document.getElementById('modalContent');
        if (modalContent) {
            // Check if message contains paybill info
            const paybillMatch = message.match(/Paybill\s+(\d+),\s+Account:\s+([A-Z0-9-]+)/i);
            
            modalContent.innerHTML = `
                <div class="mb-4">
                    <div class="text-danger" style="font-size: 4rem;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <h5 class="mb-3 text-danger">Payment Failed</h5>
                <p class="text-muted mb-4">${message}</p>
                ${paybillMatch ? `
                    <div class="alert alert-info border small">
                        <strong><i class="bi bi-info-circle"></i> Pay Manually:</strong>
                        <p class="mb-2 mt-2">M-Pesa → Lipa na M-Pesa → Paybill</p>
                        <div class="fw-bold">
                            Business: <span class="badge bg-primary">${paybillMatch[1]}</span><br>
                            Account: <span class="badge bg-secondary">${paybillMatch[2]}</span>
                        </div>
                    </div>
                ` : `
                    <div class="alert alert-light border small text-start">
                        <strong>Common reasons:</strong>
                        <ul class="mb-0 mt-2">
                            <li>You cancelled the payment</li>
                            <li>Incorrect M-Pesa PIN</li>
                            <li>Insufficient M-Pesa balance</li>
                            <li>Request timed out</li>
                        </ul>
                    </div>
                `}
                <button type="button" class="btn btn-primary" onclick="closeModal(); window.location.reload();">
                    ${paybillMatch ? 'Close' : 'Try Again'}
                </button>
            `;
        }
    }
    
    function showPaymentTimeout() {
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
    }
    
    function showPaymentError(message) {
        const modalContent = document.getElementById('modalContent');
        if (modalContent) {
            // Check if message contains paybill fallback
            const hasPaybill = message.includes('Paybill');
            
            modalContent.innerHTML = `
                <div class="mb-4">
                    <div class="text-danger" style="font-size: 3rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
                <h5 class="mb-3 text-danger">Payment Unavailable</h5>
                <p class="text-muted mb-4">${message}</p>
                ${hasPaybill ? `
                    <div class="alert alert-info border">
                        <strong><i class="bi bi-info-circle"></i> Alternative Payment:</strong>
                        <p class="mb-2 mt-2">Go to M-Pesa → Lipa na M-Pesa → Paybill</p>
                        <div class="fw-bold">
                            Business: <span class="badge bg-primary">522533</span><br>
                            Account: Check invoice details
                        </div>
                    </div>
                ` : ''}
                <button type="button" class="btn btn-secondary" onclick="closeModal();">
                    Close
                </button>
            `;
        }
    }
});

function closeModal() {
    const modal = document.getElementById('stkInvoiceModal');
    if (modal) {
        modal.remove();
    }
}
</script>
@endpush
