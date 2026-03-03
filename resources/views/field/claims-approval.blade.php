@extends('field.layouts.app')

@section('title', 'Approve Claims')
@section('page-title', 'Approve Claims')

@section('content')
<div class="page-title">
    <i class="bi bi-file-earmark-check"></i>
    Approve Worker Withdrawals
</div>
<p class="page-subtitle">Review and approve pending withdrawal requests from your workers</p>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-funnel"></i>
        Filter by Site
    </div>
    <form method="GET" action="{{ route('field.claims-approval') }}" class="row g-2">
        <div class="col-md-4">
            <select class="form-select" name="site_id" onchange="this.form.submit()">
                <option value="">All Sites</option>
                @foreach($foremanSiteIds as $siteId)
                    @php $site = \App\Models\Site::find($siteId); @endphp
                    <option value="{{ $siteId }}" {{ $selectedSiteId == $siteId ? 'selected' : '' }}>
                        {{ $site?->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-hourglass-split"></i>
        Pending Claims
    </div>
    @if(is_countable($claims) && count($claims) > 0)
        <form method="POST" action="{{ route('field.claims.bulk-action') }}" class="row g-3">
            @csrf
            <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
            <input type="hidden" name="action" id="actionInput">

            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-section mb-0">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Worker</th>
                                <th>Amount</th>
                                <th>Requested</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($claims as $claim)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input claim-checkbox" name="claim_ids[]" value="{{ $claim->id }}">
                                    </td>
                                    <td class="fw-semibold">{{ $claim->worker->name ?? '—' }}</td>
                                    <td>KES {{ number_format($claim->requested_amount, 2) }}</td>
                                    <td>{{ $claim->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ substr($claim->reason ?? '—', 0, 40) }}{{ strlen($claim->reason ?? '') > 40 ? '...' : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-2">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success w-100" 
                            onclick="document.getElementById('actionInput').value='approve'; document.querySelector('form').submit();">
                            <i class="bi bi-check-circle"></i> Approve Selected
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-danger w-100"
                            onclick="document.getElementById('actionInput').value='reject'; document.querySelector('form').submit();">
                            <i class="bi bi-x-circle"></i> Reject Selected
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <div class="mt-3">
            {{ $claims->links() }}
        </div>
    @else
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2">No pending claims</p>
        </div>
    @endif
</div>

<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.claim-checkbox').forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection
