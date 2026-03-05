@extends('owner.layouts.app')

@section('title', 'Progress Log Detail')
@section('page-title', 'Site Progress Log')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('owner.inventory.index', ['site_id' => $progressLog->site_id]) }}">Inventory Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Progress Log Detail</li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Main Header Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-2">{{ $progressLog->title }}</h4>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-primary">{{ $progressLog->site->name }}</span>
                        <span class="badge bg-light text-dark">{{ \Carbon\Carbon::parse($progressLog->log_date)->format('M d, Y') }}</span>
                        @if($progressLog->sector)
                            <span class="badge bg-info">{{ $progressLog->sector }}</span>
                        @endif
                        <span class="badge bg-{{ $progressLog->status === 'submitted' ? 'warning' : ($progressLog->status === 'approved' ? 'success' : 'secondary') }}">
                            {{ strtoupper($progressLog->status) }}
                        </span>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-person"></i> Submitted by <strong>{{ $progressLog->creator->name }}</strong>
                        · <i class="bi bi-clock"></i> {{ $progressLog->created_at->format('M d, Y H:i') }}
                    </div>
                </div>
                <a href="{{ route('owner.inventory.index', ['site_id' => $progressLog->site_id]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <hr>

            <!-- Full Description -->
            <div class="mb-3">
                <h6 class="text-muted">DESCRIPTION</h6>
                <p class="mb-0" style="white-space: pre-wrap;">{{ $progressLog->description }}</p>
            </div>
        </div>
    </div>

    <!-- Evidence Photos Gallery -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-images"></i> Evidence Photos ({{ $progressLog->evidences->count() }})</h6>
        </div>
        <div class="card-body">
            @if($progressLog->evidences->count() > 0)
                <div class="row g-3">
                    @foreach($progressLog->evidences as $evidence)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card h-100">
                                <a href="{{ asset('storage/' . $evidence->file_path) }}" target="_blank" class="text-decoration-none">
                                    <img src="{{ asset('storage/' . $evidence->file_path) }}" 
                                         class="card-img-top" 
                                         alt="Evidence photo"
                                         style="height: 200px; object-fit: cover; cursor: pointer;"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal{{ $evidence->id }}">
                                </a>
                                <div class="card-body p-2">
                                    @if($evidence->caption)
                                        <p class="small text-muted mb-1">{{ $evidence->caption }}</p>
                                    @endif
                                    <p class="small text-muted mb-0">
                                        <i class="bi bi-person-circle"></i> {{ $evidence->uploader->name ?? 'N/A' }}
                                    </p>
                                    <p class="small text-muted mb-0">
                                        <i class="bi bi-calendar3"></i> {{ $evidence->created_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Image Modal -->
                        <div class="modal fade" id="imageModal{{ $evidence->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ $evidence->caption ?? 'Evidence Photo' }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="{{ asset('storage/' . $evidence->file_path) }}" 
                                             class="img-fluid" 
                                             alt="Evidence photo">
                                    </div>
                                    <div class="modal-footer">
                                        <a href="{{ asset('storage/' . $evidence->file_path) }}" 
                                           download 
                                           class="btn btn-primary btn-sm">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                    <p class="mb-0 mt-2">No evidence photos attached to this progress log.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Status Control Panel (Owner Actions) -->
    <div class="card">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-gear"></i> Status Management</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('owner.inventory.progress.update-status', $progressLog) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Change Status</label>
                        <select name="status" class="form-select" required>
                            <option value="submitted" {{ $progressLog->status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="approved" {{ $progressLog->status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="reviewed" {{ $progressLog->status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        </select>
                        <div class="form-text">Mark this progress log as approved or reviewed.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Review Notes (Optional)</label>
                        <textarea name="review_notes" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Add notes about your review or approval..."></textarea>
                        <div class="form-text">These notes are for your records only.</div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Development Timeline Context -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> About This Log</h6>
        </div>
        <div class="card-body">
            <p class="mb-2">
                This progress log documents the site development activity for <strong>{{ $progressLog->site->name }}</strong> 
                on {{ \Carbon\Carbon::parse($progressLog->log_date)->format('l, F j, Y') }}.
            </p>
            @if($progressLog->sector)
                <p class="mb-2">
                    Work was conducted in the <span class="badge bg-info">{{ $progressLog->sector }}</span> sector of the site.
                </p>
            @endif
            <p class="mb-0 text-muted small">
                Progress logs help you track site development milestones, verify foreman activities, 
                and maintain a complete visual record of construction progress over time.
            </p>
        </div>
    </div>
</div>
@endsection
