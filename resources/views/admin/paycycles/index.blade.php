@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pay Cycles</h1>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.paycycles.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search site..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="site_id" class="form-select">
                        <option value="">All Sites</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.paycycles.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Paycycles Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Period</th>
                            <th>Worker Days</th>
                            <th>Total Hours</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paycycles as $paycycle)
                            <tr>
                                <td>{{ $paycycle->site->name }}</td>
                                <td>
                                    {{ $paycycle->start_date->format('M d') }} - 
                                    {{ $paycycle->end_date->format('M d, Y') }}
                                </td>
                                <td>{{ $paycycle->total_worker_days }}</td>
                                <td>{{ $paycycle->total_hours ?? 0 }} hrs</td>
                                <td>KES {{ number_format($paycycle->total_amount, 2) }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'info',
                                            'rejected' => 'danger',
                                            'processing' => 'primary',
                                            'completed' => 'success',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$paycycle->status] ?? 'secondary' }}">
                                        {{ ucfirst($paycycle->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.paycycles.show', $paycycle) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No pay cycles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $paycycles->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
