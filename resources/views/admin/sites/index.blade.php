@extends('admin.layouts.app')

@section('content')
<div class="container-lg py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h2 text-dark">All Sites</h1>
            <p class="text-muted">Manage all construction sites on Mjengo</p>
        </div>
        <a href="{{ route('admin.sites.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Create Site
        </a>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search by site name or location..." value="{{ $search }}">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search)
                    <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Sites Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Site Name</th>
                    <th>Location</th>
                    <th>Owner</th>
                    <th>Workers</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sites as $site)
                    <tr>
                        <td class="fw-semibold">{{ $site->name }}</td>
                        <td>{{ $site->location ?? '-' }}</td>
                        <td>{{ $site->owner->name }}</td>
                        <td>
                            <span class="badge bg-info">
                                {{ $site->workers()->whereNull('ended_at')->count() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $site->is_completed ? 'success' : 'warning' }}">
                                {{ $site->is_completed ? 'Completed' : 'Active' }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $site->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('admin.sites.edit', $site) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No sites found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $sites->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
