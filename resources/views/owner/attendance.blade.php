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
        <div class="d-flex gap-2 align-items-center">
            <span class="badge text-bg-light border">{{ $workers->count() }} workers</span>
            @if($date >= now()->startOfWeek()->toDateString() && $workers->count() > 0)
                <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()">
                    <i class="bi bi-check-all"></i> Mark All Present
                </button>
            @endif
        </div>
    </div>
    @if($date >= now()->startOfWeek()->toDateString() && $workers->count() > 0)
        <form method="POST" action="{{ route('owner.attendance.bulk-mark') }}" id="bulkAttendanceForm">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Site</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 100px;">Hours</th>
                            <th style="width: 110px;">Check-in</th>
                            <th style="width: 110px;">Check-out</th>
                            <th style="width: 80px;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workers as $index => $assignment)
                            @php
                                $attendance = $attendanceRecords->get($assignment->user_id);
                                $isPresent = $attendance ? $attendance->is_present : true;
                                $hours = $attendance->hours ?? 8;
                                // HTML5 time input requires HH:mm format (no seconds)
                                $checkIn = $attendance && $attendance->check_in ? substr($attendance->check_in, 0, 5) : '07:00';
                                $checkOut = $attendance && $attendance->check_out ? substr($attendance->check_out, 0, 5) : '15:00';
                                $isExisting = $attendance ? true : false;
                            @endphp
                            <tr class="{{ $isExisting ? 'table-light' : '' }}">
                                <td>
                                    <strong>{{ $assignment->user->name ?? 'Unknown' }}</strong>
                                    @if($isExisting)
                                        <span class="badge text-bg-success ms-2" title="Attendance already recorded today">Already Recorded</span>
                                    @else
                                        <span class="badge text-bg-primary ms-2" title="No attendance recorded yet">Not Yet Recorded</span>
                                    @endif
                                    <input type="hidden" name="attendance[{{ $index }}][worker_id]" value="{{ $assignment->user_id }}">
                                    <input type="hidden" name="attendance[{{ $index }}][site_id]" value="{{ $assignment->site_id }}">
                                </td>
                                <td><small class="text-muted">{{ $assignment->site->name ?? '—' }}</small></td>
                                <td>
                                    <select class="form-select form-select-sm" name="attendance[{{ $index }}][is_present]" data-row="{{ $index }}">
                                        <option value="1" {{ $isPresent ? 'selected' : '' }}>✓ Present</option>
                                        <option value="0" {{ !$isPresent ? 'selected' : '' }}>✗ Absent</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="attendance[{{ $index }}][hours]" value="{{ $hours }}" step="0.5" min="0" max="24" data-row="{{ $index }}">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm" name="attendance[{{ $index }}][check_in]" value="{{ $checkIn }}" data-row="{{ $index }}">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm" name="attendance[{{ $index }}][check_out]" value="{{ $checkOut }}" data-row="{{ $index }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#notes{{ $index }}" title="Add notes">
                                        <i class="bi bi-chat-left-text"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="notes{{ $index }}">
                                <td colspan="7" class="bg-light">
                                    <textarea class="form-control form-control-sm" name="attendance[{{ $index }}][reason]" rows="2" placeholder="Optional notes for this worker (e.g., late arrival, early leave, overtime)">{{ $attendance->reason ?? '' }}</textarea>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
                <small class="text-muted">All changes will be saved when you click the button</small>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save All Changes
                </button>
            </div>
        </form>
    @else
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
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">No workers found for selected site/date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script>
// Helper function to convert time to H:i format (HH:mm)
function ensureTimeFormat(timeStr) {
    if (!timeStr) return null;
    // Already in H:i format
    if (timeStr.match(/^\d{1,2}:\d{2}$/)) return timeStr;
    // Strip seconds if present (H:i:s to H:i)
    if (timeStr.match(/^\d{1,2}:\d{2}:\d{2}$/)) return timeStr.substring(0, 5);
    return timeStr;
}

// Store original times for restoration
const originalTimes = {};
document.querySelectorAll('input[name*="[check_in]"]').forEach((input, idx) => {
    const row = input.dataset.row;
    const checkInValue = input.value ? ensureTimeFormat(input.value) : null;
    const checkOutInput = document.querySelector(`input[name="attendance[${row}][check_out]"]`);
    const checkOutValue = checkOutInput.value ? ensureTimeFormat(checkOutInput.value) : null;
    
    originalTimes[row] = {
        checkIn: checkInValue,
        checkOut: checkOutValue
    };
});

function markAllPresent() {
    // Set all status dropdowns to Present
    document.querySelectorAll('select[name*="[is_present]"]').forEach(select => {
        select.value = '1';
        select.dispatchEvent(new Event('change'));
    });
}

// Auto-adjust hours, times when status changes to Absent
document.querySelectorAll('select[name*="[is_present]"]').forEach(select => {
    select.addEventListener('change', function() {
        const row = this.dataset.row;
        const hoursInput = document.querySelector(`input[name="attendance[${row}][hours]"]`);
        const checkInInput = document.querySelector(`input[name="attendance[${row}][check_in]"]`);
        const checkOutInput = document.querySelector(`input[name="attendance[${row}][check_out]"]`);
        
        if (this.value === '0') { // Absent
            hoursInput.value = '0';
            checkInInput.value = '';
            checkOutInput.value = '';
        } else { // Present
            if (hoursInput.value === '0') {
                hoursInput.value = '8';
            }
            // Restore original times if they exist, otherwise use defaults
            if (originalTimes[row] && originalTimes[row].checkIn) {
                checkInInput.value = originalTimes[row].checkIn;
                checkOutInput.value = originalTimes[row].checkOut || '15:00:00';
            } else {
                checkInInput.value = '07:00:00';
                checkOutInput.value = '15:00:00';
            }
        }
    });
});
</script>
@endpush

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
