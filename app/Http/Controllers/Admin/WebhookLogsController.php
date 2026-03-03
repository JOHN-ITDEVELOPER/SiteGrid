<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = WebhookLog::query();

        if ($request->filled('integration')) {
            $query->where('integration', $request->integration);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', '%' . $request->search . '%')
                  ->orWhere('event_type', 'like', '%' . $request->search . '%');
            });
        }

        $logs = $query->latest()->paginate(50);
        
        $integrations = WebhookLog::select('integration')->distinct()->pluck('integration');

        return view('admin.webhooks.index', compact('logs', 'integrations'));
    }

    public function show(WebhookLog $log)
    {
        return view('admin.webhooks.show', compact('log'));
    }

    public function retry(WebhookLog $log)
    {
        // Increment retry count
        $log->retry_count++;
        $log->last_retry_at = now();
        $log->status = 'pending';
        $log->save();

        // Here you would trigger the actual webhook retry
        // dispatch(new RetryWebhookJob($log));

        return back()->with('success', 'Webhook queued for retry.');
    }
}
