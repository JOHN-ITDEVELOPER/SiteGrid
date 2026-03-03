<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityFeedController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $activities = $query->latest()->paginate(50);
        
        $types = ActivityLog::select('type')->distinct()->pluck('type');

        return view('admin.activity.index', compact('activities', 'types'));
    }

    public function latest()
    {
        $activities = ActivityLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($activities);
    }
}
