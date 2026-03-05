<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayoutOverrideController extends Controller
{
    /**
     * Approve pending payout
     */
    public function approve(Request $request, Payout $payout)
    {
        $payout->load('worker', 'payCycle.site');

        // Only allow approving pending payouts
        if ($payout->status !== 'pending' && $payout->status !== 'approved') {
            return back()->with('error', 'Only pending or approved payouts can be approved.');
        }

        $payout->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'status' => $payout->status === 'pending' ? 'approved' : $payout->status,
        ]);

        ActivityLog::create([
            'type' => 'payout_override_approved',
            'severity' => 'info',
            'message' => "Payout for {$payout->worker->name} (KES {$payout->net_amount}) approved by admin",
            'user_id' => Auth::id(),
            'entity_type' => 'Payout',
            'entity_id' => $payout->id,
            'metadata' => [
                'worker_name' => $payout->worker->name,
                'site_name' => $payout->payCycle->site->name,
                'amount' => $payout->net_amount,
            ],
        ]);

        return back()->with('success', "Payout for {$payout->worker->name} approved successfully.");
    }

    /**
     * Reject pending payout
     */
    public function reject(Request $request, Payout $payout)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $payout->load('worker', 'payCycle.site');

        // Only allow rejecting pending payouts
        if ($payout->status !== 'pending' && !in_array($payout->approval_status, ['pending', 'approved'])) {
            return back()->with('error', 'Only pending payouts can be rejected.');
        }

        $payout->update([
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
            'status' => 'failed',
        ]);

        ActivityLog::create([
            'type' => 'payout_override_rejected',
            'severity' => 'warning',
            'message' => "Payout for {$payout->worker->name} rejected: {$validated['rejection_reason']}",
            'user_id' => Auth::id(),
            'entity_type' => 'Payout',
            'entity_id' => $payout->id,
            'metadata' => [
                'worker_name' => $payout->worker->name,
                'site_name' => $payout->payCycle->site->name,
                'amount' => $payout->net_amount,
                'reason' => $validated['rejection_reason'],
            ],
        ]);

        return back()->with('success', "Payout for {$payout->worker->name} rejected.");
    }

    /**
     * Bulk approve payouts
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'payout_ids' => 'required|array|min:1',
            'payout_ids.*' => 'integer|exists:payouts,id',
        ]);

        $payouts = Payout::whereIn('id', $validated['payout_ids'])
            ->where('approval_status', 'pending')
            ->get();

        $count = 0;
        foreach ($payouts as $payout) {
            $payout->update([
                'approval_status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'status' => $payout->status === 'pending' ? 'approved' : $payout->status,
            ]);
            $count++;
        }

        ActivityLog::create([
            'type' => 'payout_bulk_approved',
            'severity' => 'info',
            'message' => "Bulk approved {$count} payouts",
            'user_id' => Auth::id(),
            'entity_type' => 'Payout',
        ]);

        return back()->with('success', "{$count} payout(s) approved successfully.");
    }

    /**
     * Bulk reject payouts
     */
    public function bulkReject(Request $request)
    {
        $validated = $request->validate([
            'payout_ids' => 'required|array|min:1',
            'payout_ids.*' => 'integer|exists:payouts,id',
            'rejection_reason' => 'required|string|max:500',
        ]);

        $payouts = Payout::whereIn('id', $validated['payout_ids'])
            ->where('approval_status', 'pending')
            ->get();

        $count = 0;
        foreach ($payouts as $payout) {
            $payout->update([
                'approval_status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'status' => 'failed',
            ]);
            $count++;
        }

        ActivityLog::create([
            'type' => 'payout_bulk_rejected',
            'severity' => 'warning',
            'message' => "Bulk rejected {$count} payouts: {$validated['rejection_reason']}",
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', "{$count} payout(s) rejected.");
    }
}
