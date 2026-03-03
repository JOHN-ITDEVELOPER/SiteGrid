@extends('owner.layouts.app')

@section('title', 'Disputes & Escrow')
@section('page-title', 'Disputes & Escrow Controls')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Held Count</div><div class="h5 mb-0">{{ $summary['held_count'] }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Disputed Count</div><div class="h5 mb-0">{{ $summary['disputed_count'] }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Held Amount</div><div class="h5 mb-0">KES {{ number_format($summary['held_amount'], 2) }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Disputed Amount</div><div class="h5 mb-0 text-danger">KES {{ number_format($summary['disputed_amount'], 2) }}</div></div></div>
</div>

<div class="card kpi-card">
    <div class="card-header bg-white border-0">
        <h6 class="mb-0">Held / Disputed Payouts</h6>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Amount</th>
                    <th>Escrow Status</th>
                    <th>Reason</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disputed as $payout)
                    <tr>
                        <td>{{ $payout->worker->name ?? 'Unknown' }}</td>
                        <td>{{ $payout->payCycle->site->name ?? '—' }}</td>
                        <td>KES {{ number_format($payout->net_amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $payout->escrow_status === 'held' ? 'text-bg-warning' : 'text-bg-danger' }}">
                                {{ ucfirst($payout->escrow_status) }}
                            </span>
                        </td>
                        <td>{{ $payout->escrow_reason ?: 'No reason captured' }}</td>
                        <td>{{ $payout->updated_at->format('d M Y H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('owner.payouts.acknowledge-dispute', $payout) }}" class="d-grid gap-1">
                                @csrf
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="Acknowledgement note" required>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="confirm_2fa" value="1" id="dispute2fa{{ $payout->id }}" required>
                                    <label class="form-check-label small" for="dispute2fa{{ $payout->id }}">2FA confirmed</label>
                                </div>
                                <button class="btn btn-sm btn-outline-warning">Acknowledge</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No held or disputed payouts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $disputed->links() }}</div>
@endsection
