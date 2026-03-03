@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-link text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Attendance
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Attendance Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Attendance Record</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>Worker:</strong><br>
                            {{ $attendance->user->name }}<br>
                            <small class="text-muted">{{ $attendance->user->phone }}</small>
                        </div>
                        <div class="col-6">
                            <strong>Site:</strong><br>
                            {{ $attendance->site->name }}<br>
                            <small class="text-muted">{{ $attendance->site->location }}</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4">
                            <strong>Date:</strong><br>
                            {{ $attendance->date->format('M d, Y') }}
                        </div>
                        <div class="col-4">
                            <strong>Status:</strong><br>
                            @if($attendance->is_present)
                                <span class="badge bg-success">Present</span>
                            @else
                                <span class="badge bg-danger">Absent</span>
                            @endif
                        </div>
                        <div class="col-4">
                            <strong>Source:</strong><br>
                            <span class="badge bg-info">{{ ucfirst($attendance->source ?? 'manual') }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-4">
                            <strong>Check In:</strong><br>
                            {{ $attendance->check_in ?? 'N/A' }}
                        </div>
                        <div class="col-4">
                            <strong>Check Out:</strong><br>
                            {{ $attendance->check_out ?? 'N/A' }}
                        </div>
                        <div class="col-4">
                            <strong>Hours Worked:</strong><br>
                            {{ $attendance->hours ?? 0 }} hrs
                        </div>
                    </div>
                </div>
            </div>

            <!-- Correction History -->
            @if($attendance->corrections->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Correction History</h5>
                    </div>
                    <div class="card-body">
                        @foreach($attendance->corrections as $correction)
                            <div class="border-start border-3 border-warning ps-3 mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $correction->correctedBy->name }}</strong>
                                        <span class="badge bg-{{ $correction->status === 'approved' ? 'success' : 'warning' }}">
                                            {{ ucfirst($correction->status) }}
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $correction->created_at->format('M d, Y H:i') }}</small>
                                </div>
                                
                                <div class="mt-2">
                                    <strong>Changes:</strong><br>
                                    @if($correction->original_check_in !== $correction->corrected_check_in)
                                        Check In: <del class="text-danger">{{ $correction->original_check_in }}</del> 
                                        <span class="text-success">{{ $correction->corrected_check_in }}</span><br>
                                    @endif
                                    @if($correction->original_check_out !== $correction->corrected_check_out)
                                        Check Out: <del class="text-danger">{{ $correction->original_check_out }}</del> 
                                        <span class="text-success">{{ $correction->corrected_check_out }}</span><br>
                                    @endif
                                    @if($correction->original_hours !== $correction->corrected_hours)
                                        Hours: <del class="text-danger">{{ $correction->original_hours }}</del> 
                                        <span class="text-success">{{ $correction->corrected_hours }}</span><br>
                                    @endif
                                </div>
                                
                                <div class="mt-2">
                                    <strong>Reason:</strong><br>
                                    {{ $correction->reason }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Correction Form -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square"></i> Make Correction
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.attendance.correct', $attendance) }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Corrected Check In</label>
                            <input type="time" name="corrected_check_in" class="form-control" 
                                   value="{{ old('corrected_check_in', $attendance->check_in) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Corrected Check Out</label>
                            <input type="time" name="corrected_check_out" class="form-control" 
                                   value="{{ old('corrected_check_out', $attendance->check_out) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Correction *</label>
                            <textarea name="reason" class="form-control" rows="4" required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-check2"></i> Apply Correction
                        </button>
                    </form>

                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <i class="bi bi-info-circle"></i>
                            Corrections are auto-approved and logged for audit.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
