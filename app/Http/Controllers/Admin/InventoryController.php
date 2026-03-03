<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\Site;
use App\Models\SiteProgressLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $siteId = $request->input('site_id');

        $metrics = [
            'sites_enabled' => Site::where('inventory_enabled', true)->count(),
            'pending_requests' => ProcurementRequest::where('status', 'requested')->count(),
            'approved_not_received' => ProcurementRequest::whereIn('status', ['approved', 'po_issued'])->count(),
            'low_stock_alerts' => InventoryStock::whereColumn('quantity', '<=', 'low_stock_threshold')
                ->where('low_stock_threshold', '>', 0)
                ->count(),
            'movements_today' => InventoryMovement::whereDate('created_at', now()->toDateString())->count(),
            'progress_logs_today' => SiteProgressLog::whereDate('log_date', now()->toDateString())->count(),
        ];

        $requests = ProcurementRequest::with(['site', 'requester', 'approver'])
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest()
            ->limit(25)
            ->get();

        $lowStock = InventoryStock::with(['site', 'item.category'])
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('low_stock_threshold', '>', 0)
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->orderBy('quantity')
            ->limit(30)
            ->get();

        $movements = InventoryMovement::with(['site', 'item', 'performedBy'])
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest()
            ->limit(30)
            ->get();

        $sites = Site::select('id', 'name')->orderBy('name')->get();

        return view('admin.inventory.index', compact('metrics', 'requests', 'lowStock', 'movements', 'sites', 'siteId'));
    }
}
