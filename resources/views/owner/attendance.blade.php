@extends('owner.layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Mark Attendance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Daily Attendance Management</h6>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
        <i class="bi bi-download"></i> Export
    </button>
</div>

@if($date < now()->startOfWeek()->toDateString())
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Past Week Selected:</strong> Owners can only mark or update attendance from the current week (starting {{ now()->startOfWeek()->format('M d, Y') }}). Previous week attendance is locked to ensure accurate payroll.
    </div>
@endif

<div class="card kpi-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('owner.attendance') }}">
            <div class="col-md-4">
                <label class="form-label small">Site</label>
                <select class="form-select" name="site_id">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ (string)$siteId === (string)$site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Date</label>
                <input type="date" class="form-control" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}">
                <small class="form-text text-muted">Can only edit current week attendance</small>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card kpi-card">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Attendance for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h6>
        <span class="badge text-bg-light border">{{ $workers->count() }} workers</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Status</th>
                    <th>Hours</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $assignment)
                    @php
                        $attendance = $attendanceRecords->get($assignment->user_id);
                        $isPresent = $attendance && $attendance->is_present;
                        $hours = $attendance->hours ?? 0;
                        $checkIn = $attendance->check_in ?? '';
                        $checkOut = $attendance->check_out ?? '';
                    @endphp
                    <tr>
                        <td>{{ $assignment->user->name ?? 'Unknown' }}</td>
                        <td>{{ $assignment->site->name ?? '—' }}</td>
                        <td>
                            @if($attendance)
                                <span class="badge {{ $isPresent ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $isPresent ? 'Present' : 'Absent' }}
                                </span>
                            @else
                                <span class="badge text-bg-light border">Not Marked</span>
                            @endif
                        </td>
                        <td>{{ number_format($hours, 1) }}</td>
                        <td>{{ $checkIn }}</td>
                        <td>{{ $checkOut }}</td>
                        <td>
                            @if($date >= now()->startOfWeek()->toDateString())
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal{{ $assignment->id }}">
                                    {{ $attendance ? 'Edit' : 'Mark' }}
                                </button>
                            @else
                                <span class="text-muted small">Locked</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Attendance Modal -->
                    <div class="modal fade" id="attendanceModal{{ $assignment->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('owner.attendance.mark') }}">
                                    @csrf
                                    <input type="hidden" name="site_id" value="{{ $assignment->site_id }}">
                                    <input type="hidden" name="worker_id" value="{{ $assignment->user_id }}">
                                    <input type="hidden" name="date" value="{{ $date }}">
                                    
                                    <div class="modal-header">
                                        <h5 class="modal-title">Mark Attendance: {{ $assignment->user->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Status *</label>
                                            <select class="form-select" name="is_present" required>
                                                <option value="1" {{ $isPresent ? 'selected' : '' }}>Present</option>
                                                <option value="0" {{ !$isPresent ? 'selected' : '' }}>Absent</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Hours Worked</label>
                                            <input type="number" step="0.5" class="form-control" name="hours" value="{{ $hours }}" min="0" max="24">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Check-in Time</label>
                                                <input type="time" class="form-control" name="check_in" value="{{ $checkIn }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Check-out Time</label>
                                                <input type="time" class="form-control" name="check_out" value="{{ $checkOut }}">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Notes (optional)</label>
                                            <textarea class="form-control" name="reason" rows="2" placeholder="Reason for manual entry or adjustment"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="7" class="text-muted">No workers found for selected site/date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ route('owner.exports.attendance') }}">
                <div class="modal-header">
                    <h5 class="modal-title">Export Attendance Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Site (optional)</label>
                        <select class="form-select" name="site_id">
                            <option value="">All Sites</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date *</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date *</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Download CSV</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
