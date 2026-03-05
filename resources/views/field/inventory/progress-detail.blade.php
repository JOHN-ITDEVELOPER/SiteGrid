@extends('field.layouts.app')

@section('title', 'Progress Log Detail')
@section('page-title', 'My Progress Log')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('field.inventory.index', ['site_id' => $progressLog->site_id]) }}">Inventory Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Progress Log Detail</li>
        </ol>
    </nav>

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
                        <i class="bi bi-clock"></i> Submitted {{ $progressLog->created_at->format('M d, Y H:i') }}
                    </div>
                </div>
                <a href="{{ route('field.inventory.index', ['site_id' => $progressLog->site_id]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
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
                                <img src="{{ asset('storage/' . $evidence->file_path) }}" 
                                     class="card-img-top" 
                                     alt="Evidence photo"
                                     style="height: 200px; object-fit: cover; cursor: pointer;"
                                     data-bs-toggle="modal" 
                                     data-bs-target="#imageModal{{ $evidence->id }}">
                                <div class="card-body p-2">
                                    @if($evidence->caption)
                                        <p class="small text-muted mb-1">{{ $evidence->caption }}</p>
                                    @endif
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

    <!-- Status Info Card -->
    <div class="card">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Submission Status</h6>
        </div>
        <div class="card-body">
            <p class="mb-0">
                @if($progressLog->status === 'submitted')
                    <strong>Status: Pending Review</strong>
                    <br/>
                    <span class="text-muted">Your progress log has been submitted and is awaiting review by the site owner.</span>
                @elseif($progressLog->status === 'approved')
                    <strong class="text-success">Status: Approved</strong>
                    <br/>
                    <span class="text-muted">The site owner has approved this progress log.</span>
                @else
                    <strong class="text-secondary">Status: Reviewed</strong>
                    <br/>
                    <span class="text-muted">The site owner has reviewed this progress log.</span>
                @endif
            </p>
        </div>
    </div>
</div>
@endsection
