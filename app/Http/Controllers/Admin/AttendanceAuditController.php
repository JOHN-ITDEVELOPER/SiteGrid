<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Site;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['site', 'user']);

        // Filters
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $attendances = $query->latest('date')->paginate(50);
        $sites = Site::orderBy('name')->get();
        $workers = User::where('role', 'worker')->orderBy('name')->get();

        return view('admin.attendance.index', compact('attendances', 'sites', 'workers'));
    }

    public function show(Attendance $attendance)
    {
        $attendance->load(['site', 'user', 'corrections.correctedBy', 'corrections.reviewedBy']);
        return view('admin.attendance.show', compact('attendance'));
    }

    public function correct(Request $request, Attendance $attendance)
    {
        $request->validate([
            'corrected_check_in' => 'nullable|date_format:H:i',
            'corrected_check_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:500',
        ]);

        $correction = new AttendanceCorrection();
        $correction->attendance_id = $attendance->id;
        $correction->corrected_by = Auth::id();
        $correction->original_check_in = $attendance->check_in;
        $correction->original_check_out = $attendance->check_out;
        $correction->original_hours = $attendance->hours;
        $correction->corrected_check_in = $request->corrected_check_in;
        $correction->corrected_check_out = $request->corrected_check_out;
        
        // Calculate corrected hours
        if ($request->corrected_check_in && $request->corrected_check_out) {
            $checkIn = \Carbon\Carbon::parse($request->corrected_check_in);
            $checkOut = \Carbon\Carbon::parse($request->corrected_check_out);
            $correction->corrected_hours = $checkOut->diffInHours($checkIn);
        }
        
        $correction->reason = $request->reason;
        $correction->status = 'approved'; // Auto-approve admin corrections
        $correction->reviewed_by = Auth::id();
        $correction->reviewed_at = now();
        $correction->save();

        // Apply correction
        $attendance->check_in = $request->corrected_check_in ?? $attendance->check_in;
        $attendance->check_out = $request->corrected_check_out ?? $attendance->check_out;
        $attendance->hours = $correction->corrected_hours ?? $attendance->hours;
        $attendance->save();

        // Log activity
        ActivityLog::create([
            'type' => 'attendance_corrected',
            'severity' => 'warning',
            'message' => 'Attendance corrected for ' . $attendance->user->name,
            'user_id' => Auth::id(),
            'entity_type' => 'Attendance',
            'entity_id' => $attendance->id,
            'meta' => [
                'worker' => $attendance->user->name,
                'site' => $attendance->site->name,
                'date' => $attendance->date,
                'reason' => $request->reason,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.attendance.show', $attendance)->with('success', 'Attendance corrected successfully.');
    }
}
