<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class IntegrationHealthController extends Controller
{
    public function index()
    {
        // MPesa Integration Health
        $mpesaHealth = $this->getIntegrationHealth('mpesa');
        
        // USSD Integration Health
        $ussdHealth = $this->getIntegrationHealth('ussd');
        
        // SMS Integration Health
        $smsHealth = $this->getIntegrationHealth('sms');
        
        // Overall payout success rate
        $payoutStats = Payout::select('status', DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return view('admin.integration-health.index', compact(
            'mpesaHealth',
            'ussdHealth',
            'smsHealth',
            'payoutStats'
        ));
    }

    private function getIntegrationHealth($integration)
    {
        $last24h = Carbon::now()->subDay();
        $last7d = Carbon::now()->subDays(7);

        $health = [
            'name' => ucfirst($integration),
            'total_calls_24h' => WebhookLog::where('integration', $integration)
                ->where('created_at', '>=', $last24h)
                ->count(),
            'success_rate_24h' => 0,
            'total_calls_7d' => WebhookLog::where('integration', $integration)
                ->where('created_at', '>=', $last7d)
                ->count(),
            'success_rate_7d' => 0,
            'avg_response_time' => 0,
            'last_success' => WebhookLog::where('integration', $integration)
                ->where('status', 'success')
                ->latest()
                ->value('created_at'),
            'last_failure' => WebhookLog::where('integration', $integration)
                ->where('status', 'failed')
                ->latest()
                ->value('created_at'),
            'status' => 'healthy',
        ];

        // Calculate success rates
        $success24h = WebhookLog::where('integration', $integration)
            ->where('created_at', '>=', $last24h)
            ->where('status', 'success')
            ->count();
        
        $success7d = WebhookLog::where('integration', $integration)
            ->where('created_at', '>=', $last7d)
            ->where('status', 'success')
            ->count();

        $health['success_rate_24h'] = $health['total_calls_24h'] > 0 
            ? round(($success24h / $health['total_calls_24h']) * 100, 2)
            : 0;

        $health['success_rate_7d'] = $health['total_calls_7d'] > 0 
            ? round(($success7d / $health['total_calls_7d']) * 100, 2)
            : 0;

        // Determine status
        if ($health['success_rate_24h'] < 50) {
            $health['status'] = 'critical';
        } elseif ($health['success_rate_24h'] < 80) {
            $health['status'] = 'warning';
        }

        return $health;
    }
}
