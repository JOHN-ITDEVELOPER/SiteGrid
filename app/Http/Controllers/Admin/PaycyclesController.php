<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayCycle;
use App\Models\Payout;
use App\Models\Site;
use App\Models\Attendance;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaycyclesController extends Controller
{
    public function index(Request $request)
    {
        $query = PayCycle::with(['site']);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('site', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $paycycles = $query->latest('end_date')->paginate(30);
        $sites = Site::orderBy('name')->get();

        return view('admin.paycycles.index', compact('paycycles', 'sites'));
    }

    public function show(PayCycle $paycycle)
    {
        $paycycle->load(['site', 'payouts.worker']);
        
        return view('admin.paycycles.show', compact('paycycle'));
    }

    public function recalculate(PayCycle $paycycle)
    {
        DB::beginTransaction();
        try {
            // Recalculate totals
            $attendances = Attendance::where('site_id', $paycycle->site_id)
                ->whereBetween('date', [$paycycle->start_date, $paycycle->end_date])
                ->where('is_present', true)
                ->get();

            $totalHours = $attendances->sum('hours');
            $totalWorkerDays = $attendances->count();

            $paycycle->total_hours = $totalHours;
            $paycycle->total_worker_days = $totalWorkerDays;
            $paycycle->total_amount = $totalHours * ($paycycle->site->hourly_rate ?? 100);
            $paycycle->save();

            // Recalculate individual payouts
            foreach ($paycycle->payouts as $payout) {
                $workerAttendances = Attendance::where('site_id', $paycycle->site_id)
                    ->where('user_id', $payout->worker_id)
                    ->whereBetween('date', [$paycycle->start_date, $paycycle->end_date])
                    ->where('is_present', true)
                    ->get();

                $payout->hours_worked = $workerAttendances->sum('hours');
                $payout->days_worked = $workerAttendances->count();
                $payout->gross_amount = $payout->hours_worked * ($paycycle->site->hourly_rate ?? 100);
                $payout->net_amount = $payout->gross_amount - $payout->deductions;
                $payout->save();
            }

            // Log activity
            ActivityLog::create([
                'type' => 'paycycle_recalculated',
                'severity' => 'info',
                'message' => 'Paycycle recalculated for ' . $paycycle->site->name,
                'user_id' => Auth::id(),
                'entity_type' => 'PayCycle',
                'entity_id' => $paycycle->id,
                'meta' => [
                    'site' => $paycycle->site->name,
                    'period' => $paycycle->start_date . ' to ' . $paycycle->end_date,
                    'total_amount' => $paycycle->total_amount,
                ],
                'ip_address' => request()->ip(),
            ]);

            DB::commit();
            return redirect()->route('admin.paycycles.show', $paycycle)->with('success', 'Paycycle recalculated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to recalculate paycycle: ' . $e->getMessage());
        }
    }

    public function retryPayout(Payout $payout)
    {
        if ($payout->status !== 'failed') {
            return back()->with('error', 'Only failed payouts can be retried.');
        }

        // Reset status to pending for retry
        $payout->status = 'pending';
        $payout->failure_reason = null;
        $payout->save();

        // Log activity
        ActivityLog::create([
            'type' => 'payout_retry',
            'severity' => 'info',
            'message' => 'Payout retry initiated for ' . $payout->worker->name,
            'user_id' => Auth::id(),
            'entity_type' => 'Payout',
            'entity_id' => $payout->id,
            'meta' => [
                'worker' => $payout->worker->name,
                'amount' => $payout->net_amount,
                'reference' => $payout->reference,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Here you would trigger the actual payment processing
        // This is a placeholder - you'd call your payment service
        // dispatch(new ProcessPayoutJob($payout));

        return back()->with('success', 'Payout queued for retry.');
    }
}
