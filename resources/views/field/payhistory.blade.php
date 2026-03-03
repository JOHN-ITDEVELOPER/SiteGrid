@extends('field.layouts.app')

@section('title', 'Pay History')
@section('page-title', 'Pay History')

@section('content')
<div class="page-title">
    <i class="bi bi-clock-history"></i>
    Pay History
</div>
<p class="page-subtitle">View your payment history and past payouts</p>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-list-check"></i>
        Paid Payouts
    </div>
    @if(count($payHistory) > 0)
        <div class="table-responsive">
            <table class="table table-section mb-0">
                <thead>
                    <tr>
                        <th>Pay Period</th>
                        <th>Site</th>
                        <th>Amount</th>
                        <th>Date Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payHistory as $payout)
                        <tr>
                            <td class="fw-semibold">{{ $payout->pay_cycle?->period_name ?? 'N/A' }}</td>
                            <td>{{ $payout->payCycle?->site?->name ?? '—' }}</td>
                            <td>KES {{ number_format($payout->net_amount, 2) }}</td>
                            <td>{{ $payout->paid_at?->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $payHistory->links() }}
        </div>
    @else
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2">No payment history yet</p>
        </div>
    @endif
</div>
@endsection
