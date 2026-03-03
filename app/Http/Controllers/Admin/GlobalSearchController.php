<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use App\Models\Payout;
use App\Models\Invoice;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [
            'sites' => [],
            'users' => [],
            'payouts' => [],
            'invoices' => [],
        ];

        // Search sites
        $results['sites'] = Site::where('name', 'like', "%{$query}%")
            ->orWhere('location', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($site) {
                return [
                    'id' => $site->id,
                    'title' => $site->name,
                    'subtitle' => $site->location,
                    'url' => route('admin.sites.show', $site),
                    'type' => 'site',
                    'icon' => 'bi-geo-alt',
                ];
            });

        // Search users
        $results['users'] = User::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'title' => $user->name,
                    'subtitle' => $user->phone . ' - ' . $user->role,
                    'url' => route('admin.users.show', $user),
                    'type' => 'user',
                    'icon' => 'bi-person',
                ];
            });

        // Search payouts by reference
        $results['payouts'] = Payout::where('reference', 'like', "%{$query}%")
            ->orWhereHas('worker', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->with('worker')
            ->limit(5)
            ->get()
            ->map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'title' => 'Payout ' . $payout->reference,
                    'subtitle' => $payout->worker->name . ' - KES ' . number_format($payout->net_amount, 2),
                    'url' => route('admin.payouts.show', $payout),
                    'type' => 'payout',
                    'icon' => 'bi-cash-stack',
                ];
            });

        // Search invoices
        $results['invoices'] = Invoice::where('invoice_number', 'like', "%{$query}%")
            ->orWhereHas('site', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->with('site')
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'title' => 'Invoice ' . $invoice->invoice_number,
                    'subtitle' => $invoice->site->name . ' - KES ' . number_format($invoice->total_amount, 2),
                    'url' => route('admin.invoices.show', $invoice),
                    'type' => 'invoice',
                    'icon' => 'bi-receipt',
                ];
            });

        return response()->json($results);
    }
}
