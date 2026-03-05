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
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-light text-dark border">{{ $roster->count() }} workers</span>
                @if($date >= now()->toDateString())
                    <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()">
                        <i class="bi bi-check-all"></i> Mark All Present
                    </button>
                @endif
            </div>
        </div>
        @if($date >= now()->toDateString())
            <form method="POST" action="{{ route('field.attendance.bulk-mark') }}" id="bulkAttendanceForm">
                @csrf
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
                <input type="hidden" name="date" value="{{ $date }}">
                <div class="table-responsive">
                    <table class="table table-section mb-0">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Phone</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 100px;">Hours</th>
                                <th style="width: 110px;">Check-in</th>
                                <th style="width: 110px;">Check-out</th>
                                <th style="width: 80px;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roster as $index => $worker)
                                @php
                                    $attendance = $attendanceRecords->get($worker->user_id);
                                    $isPresent = $attendance ? $attendance->is_present : true;
                                    $hours = $attendance->hours ?? 8;
                                    // HTML5 time input requires HH:mm format (no seconds)
                                    $checkIn = $attendance && $attendance->check_in ? substr($attendance->check_in, 0, 5) : '07:00';
                                    $checkOut = $attendance && $attendance->check_out ? substr($attendance->check_out, 0, 5) : '15:00';
                                    $isExisting = $attendance ? true : false;
                                @endphp
                                <tr class="{{ $isExisting ? 'table-light' : '' }}">
                                    <td>
                                        <strong>{{ $worker->user->name }}</strong>
                                        @if($isExisting)
                                            <span class="badge text-bg-success ms-2" title="Attendance already recorded today">Already Recorded</span>
                                        @else
                                            <span class="badge text-bg-primary ms-2" title="No attendance recorded yet">Not Yet Recorded</span>
                                        @endif
                                        <input type="hidden" name="attendance[{{ $index }}][worker_id]" value="{{ $worker->user_id }}">
                                    </td>
                                    <td><small class="text-muted">{{ $worker->user->phone }}</small></td>
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
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">All changes will be saved when you click the button</small>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save All Changes
                    </button>
                </div>
            </form>
        @else
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        window.markAllPresent = function() {
            // Set all status dropdowns to Present
            document.querySelectorAll('select[name*="[is_present]"]').forEach(select => {
                select.value = '1';
                select.dispatchEvent(new Event('change'));
            });
        };

        // Auto-adjust hours, times when status changes to Absent
        document.querySelectorAll('select[name*="[is_present]"]').forEach(select => {
            select.addEventListener('change', function() {
                console.log('Status changed to:', this.value); // Debug
                
                const row = this.dataset.row;
                const tableRow = this.closest('tr');
                
                // Find inputs within the same row
                const hoursInput = tableRow.querySelector(`input[name="attendance[${row}][hours]"]`);
                const checkInInput = tableRow.querySelector(`input[name="attendance[${row}][check_in]"]`);
                const checkOutInput = tableRow.querySelector(`input[name="attendance[${row}][check_out]"]`);
                
                console.log('Found inputs:', {hours: !!hoursInput, checkIn: !!checkInInput, checkOut: !!checkOutInput}); // Debug
                
                if (this.value === '0') { // Absent
                    console.log('Setting to absent'); // Debug
                    if (hoursInput) hoursInput.value = '0';
                    if (checkInInput) checkInInput.value = '';
                    if (checkOutInput) checkOutInput.value = '';
                } else { // Present
                    console.log('Setting to present'); // Debug
                    if (hoursInput && hoursInput.value === '0') {
                        hoursInput.value = '8';
                    }
                    // Restore original times if they exist, otherwise use defaults
                    if (originalTimes[row] && originalTimes[row].checkIn) {
                        if (checkInInput) checkInInput.value = originalTimes[row].checkIn;
                        if (checkOutInput) checkOutInput.value = originalTimes[row].checkOut || '15:00';
                    } else {
                        if (checkInInput) checkInInput.value = '07:00';
                        if (checkOutInput) checkOutInput.value = '15:00';
                    }
                }
            });
        });
    });
    </script>
    @endpush
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
