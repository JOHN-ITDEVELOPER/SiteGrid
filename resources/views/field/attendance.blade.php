@extends('field.layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')

@section('content')
<div class="page-title">
    <i class="bi bi-calendar-check"></i>
    My Attendance
</div>
<p class="page-subtitle">View your attendance records and hours</p>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-bars"></i>
        Attendance Records
    </div>
    @if(count($attendance) > 0)
        <div class="table-responsive">
            <table class="table table-section mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Site</th>
                        <th>Status</th>
                        <th>Hours</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendance as $record)
                        <tr>
                            <td class="fw-semibold">{{ $record->date?->format('M d, Y l') }}</td>
                            <td>{{ $record->site?->name ?? '—' }}</td>
                            <td>
                                @if($record->is_present)
                                    <span class="badge bg-success">Present</span>
                                @else
                                    <span class="badge bg-danger">Absent</span>
                                @endif
                            </td>
                            <td>{{ $record->hours ?? '—' }}</td>
                            <td class="small">{{ $record->check_in ?? '—' }}</td>
                            <td class="small">{{ $record->check_out ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $attendance->links() }}
        </div>
    @else
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2">No attendance records found</p>
        </div>
    @endif
</div>
@endsection
