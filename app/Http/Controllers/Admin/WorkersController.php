<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteWorker;
use App\Models\Site;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class WorkersController extends Controller
{
    /**
     * Display a listing of all workers across all sites
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $siteId = $request->input('site_id');
        $status = $request->input('status', 'active'); // active, inactive, all
        $isForeman = $request->input('is_foreman');

        $workers = SiteWorker::with(['user', 'site'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('site', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->when($status === 'active', fn($query) => $query->whereNull('ended_at'))
            ->when($status === 'inactive', fn($query) => $query->whereNotNull('ended_at'))
            ->when($isForeman === 'yes', fn($query) => $query->where('is_foreman', true))
            ->when($isForeman === 'no', fn($query) => $query->where('is_foreman', false))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $sites = Site::orderBy('name')->get();

        return view('admin.workers.index', compact('workers', 'sites', 'search', 'siteId', 'status', 'isForeman'));
    }

    /**
     * Show the form for creating a new worker
     */
    public function create()
    {
        $sites = Site::orderBy('name')->get();
        $users = User::whereIn('role', ['worker', 'foreman'])
            ->orderBy('name')
            ->get();
        
        return view('admin.workers.create', compact('sites', 'users'));
    }

    /**
     * Store a newly created worker
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'site_id' => 'required|exists:sites,id',
            'is_foreman' => 'boolean',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'required|numeric|min:0',
            'started_at' => 'nullable|date',
        ]);

        // Check if worker already assigned to this site
        $existing = SiteWorker::where('user_id', $validated['user_id'])
            ->where('site_id', $validated['site_id'])
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            return back()->withErrors(['user_id' => 'This worker is already assigned to this site.'])->withInput();
        }

        $worker = SiteWorker::create([
            'user_id' => $validated['user_id'],
            'site_id' => $validated['site_id'],
            'is_foreman' => $validated['is_foreman'] ?? false,
            'daily_rate' => $validated['daily_rate'],
            'weekly_rate' => $validated['weekly_rate'],
            'started_at' => $validated['started_at'] ?? now(),
        ]);

        $this->logAction('worker.create', 'SiteWorker', $worker->id, [
            'user_id' => $validated['user_id'],
            'site_id' => $validated['site_id'],
            'is_foreman' => $validated['is_foreman'] ?? false,
        ]);

        return redirect()->route('admin.workers.index')->with('success', 'Worker assigned successfully');
    }

    /**
     * Show the form for editing the specified worker
     */
    public function edit(SiteWorker $worker)
    {
        $worker->load(['user', 'site']);
        $sites = Site::orderBy('name')->get();
        
        return view('admin.workers.edit', compact('worker', 'sites'));
    }

    /**
     * Update the specified worker
     */
    public function update(Request $request, SiteWorker $worker)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'is_foreman' => 'boolean',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'required|numeric|min:0',
            'started_at' => 'nullable|date',
        ]);

        $oldData = $worker->toArray();
        $worker->update($validated);

        $this->logAction('worker.update', 'SiteWorker', $worker->id, [
            'old' => $oldData,
            'new' => $validated,
        ]);

        return redirect()->route('admin.workers.index')->with('success', 'Worker updated successfully');
    }

    /**
     * Deactivate a worker (set ended_at)
     */
    public function deactivate(SiteWorker $worker)
    {
        if ($worker->ended_at) {
            return back()->withErrors(['worker' => 'Worker is already inactive.']);
        }

        $worker->update(['ended_at' => now()]);

        $this->logAction('worker.deactivate', 'SiteWorker', $worker->id, [
            'user_id' => $worker->user_id,
            'site_id' => $worker->site_id,
        ]);

        return back()->with('success', 'Worker deactivated successfully');
    }

    /**
     * Reactivate a worker (set ended_at to null)
     */
    public function reactivate(SiteWorker $worker)
    {
        if (!$worker->ended_at) {
            return back()->withErrors(['worker' => 'Worker is already active.']);
        }

        $worker->update(['ended_at' => null]);

        $this->logAction('worker.reactivate', 'SiteWorker', $worker->id, [
            'user_id' => $worker->user_id,
            'site_id' => $worker->site_id,
        ]);

        return back()->with('success', 'Worker reactivated successfully');
    }

    /**
     * View complete worker assignment history
     */
    public function history(User $user)
    {
        $user->load(['siteWorkers' => function ($query) {
            $query->with('site')->orderByDesc('started_at');
        }]);

        return view('admin.workers.history', compact('user'));
    }

    /**
     * Bulk operations on workers
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:deactivate,reactivate,update_rates',
            'worker_ids' => 'required|array|min:1',
            'worker_ids.*' => 'exists:site_workers,id',
        ]);

        $workerIds = $request->worker_ids;
        $action = $request->action;

        switch ($action) {
            case 'deactivate':
                SiteWorker::whereIn('id', $workerIds)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => now()]);
                
                $this->logAction('workers.bulk_deactivate', null, null, ['count' => count($workerIds)]);
                return back()->with('success', count($workerIds) . ' workers deactivated.');

            case 'reactivate':
                SiteWorker::whereIn('id', $workerIds)
                    ->whereNotNull('ended_at')
                    ->update(['ended_at' => null]);
                
                $this->logAction('workers.bulk_reactivate', null, null, ['count' => count($workerIds)]);
                return back()->with('success', count($workerIds) . ' workers reactivated.');

            case 'update_rates':
                // Validate rate fields for bulk update
                $request->validate([
                    'daily_rate' => 'required|numeric|min:0',
                    'weekly_rate' => 'required|numeric|min:0',
                ]);

                SiteWorker::whereIn('id', $workerIds)->update([
                    'daily_rate' => $request->daily_rate,
                    'weekly_rate' => $request->weekly_rate,
                ]);

                $this->logAction('workers.bulk_update_rates', null, null, [
                    'count' => count($workerIds),
                    'daily_rate' => $request->daily_rate,
                    'weekly_rate' => $request->weekly_rate,
                ]);
                return back()->with('success', 'Rates updated for ' . count($workerIds) . ' workers.');

            default:
                return back()->with('error', 'Invalid action.');
        }
    }

    /**
     * Log an action to the audit log
     */
    private function logAction($action, $entityType, $entityId, $meta = [])
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
