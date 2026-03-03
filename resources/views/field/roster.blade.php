@extends('field.layouts.app')

@section('title', 'Roster - Mark Attendance')
@section('page-title', 'Mark Attendance')

@section('content')
<div class="page-title">
    <i class="bi bi-calendar-event"></i>
    Roster & Attendance
</div>
<p class="page-subtitle">Mark your workers' attendance for today</p>

@if($date < now()->toDateString())
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Past Date Selected:</strong> Foremen cannot mark or update attendance for past dates. Contact the site owner if corrections are needed.
    </div>
@endif

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-calendar-event"></i>
        Select Site & Date
    </div>
    <form method="GET" action="{{ route('field.roster') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Site</label>
            <select class="form-select" name="site_id" required onchange="this.form.submit()">
                <option value="">Select site...</option>
                @foreach($foremanSiteIds as $siteId)
                    @php $site = \App\Models\Site::find($siteId); @endphp
                    <option value="{{ $siteId }}" {{ $selectedSiteId == $siteId ? 'selected' : '' }}>
                        {{ $site?->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()">
            <small class="form-text text-muted">Cannot select future dates or edit past attendance</small>
        </div>
    </form>
</div>

@if($selectedSiteId && count($roster) > 0)
    <div class="form-section">
        <div class="form-section-title d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-people-fill"></i>
                Attendance for {{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}
            </span>
            <span class="badge bg-light text-dark border">{{ $roster->count() }} workers</span>
        </div>
        <div class="table-responsive">
            <table class="table table-section mb-0">
                <thead>
                    <tr>
                        <th>Worker</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Hours</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roster as $worker)
                        @php
                            $attendance = $attendanceRecords->get($worker->user_id);
                            $isPresent = $attendance && $attendance->is_present;
                            $hours = $attendance->hours ?? 0;
                            $checkIn = $attendance->check_in ?? '';
                            $checkOut = $attendance->check_out ?? '';
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $worker->user->name }}</td>
                            <td>{{ $worker->user->phone }}</td>
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
                                @if($date >= now()->toDateString())
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal{{ $worker->id }}">
                                        {{ $attendance ? 'Edit' : 'Mark' }}
                                    </button>
                                @else
                                    <span class="text-muted small">Locked</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Attendance Modal -->
                        <div class="modal fade" id="attendanceModal{{ $worker->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('field.attendance.mark') }}">
                                        @csrf
                                        <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
                                        <input type="hidden" name="worker_id" value="{{ $worker->user_id }}">
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        
                                        <div class="modal-header">
                                            <h5 class="modal-title">Mark Attendance: {{ $worker->user->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Status *</label>
                                                <select class="form-select" name="is_present" required>
                                                    <option value="1" {{ $isPresent ? 'selected' : '' }}>Present</option>
                                                    <option value="0" {{ !$isPresent && $attendance ? 'selected' : '' }}>Absent</option>
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif($selectedSiteId)
    <div class="form-section">
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2">No workers assigned to this site</p>
        </div>
    </div>
@else
    <div class="form-section">
        <div class="text-center py-4 text-muted">
            <i class="bi bi-arrow-left"></i>
            <p class="mt-2">Please select a site to get started</p>
        </div>
    </div>
@endif
@endsection
