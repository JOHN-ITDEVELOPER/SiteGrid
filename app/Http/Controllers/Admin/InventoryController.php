<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\Site;
use App\Models\SiteProgressLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $siteId = $request->input('site_id');

        $scopedCategoryQuery = InventoryCategory::query()
            ->when($siteId, fn($query) => $query->where('site_id', $siteId));

        $scopedItemQuery = InventoryItem::query()
            ->when($siteId, fn($query) => $query->where('site_id', $siteId));

        $metrics = [
            'sites_enabled' => InventoryCategory::select('site_id')->distinct()->count('site_id'),
            'categories_count' => $scopedCategoryQuery->count(),
            'active_items_count' => $scopedItemQuery->where('is_active', true)->count(),
            'pending_requests' => ProcurementRequest::where('status', 'requested')
                ->when($siteId, fn($query) => $query->where('site_id', $siteId))
                ->count(),
            'approved_not_received' => ProcurementRequest::whereIn('status', ['approved', 'po_issued'])
                ->when($siteId, fn($query) => $query->where('site_id', $siteId))
                ->count(),
            'low_stock_alerts' => InventoryStock::whereColumn('quantity', '<=', 'low_stock_threshold')
                ->where('low_stock_threshold', '>', 0)
                ->when($siteId, fn($query) => $query->where('site_id', $siteId))
                ->count(),
            'movements_today' => InventoryMovement::whereDate('created_at', now()->toDateString())
                ->when($siteId, fn($query) => $query->where('site_id', $siteId))
                ->count(),
            'progress_logs_today' => SiteProgressLog::whereDate('log_date', now()->toDateString())
                ->when($siteId, fn($query) => $query->where('site_id', $siteId))
                ->count(),
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

        $movements = InventoryMovement::with(['site', 'item', 'performedBy', 'evidences'])
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest()
            ->limit(30)
            ->get();

        $progressLogs = SiteProgressLog::with(['site', 'creator'])
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest('log_date')
            ->limit(20)
            ->get();

        $sites = Site::select('id', 'name')->orderBy('name')->get();

        return view('admin.inventory.index', compact('metrics', 'requests', 'lowStock', 'movements', 'progressLogs', 'sites', 'siteId'));
    }

    public function showProgress(SiteProgressLog $progressLog): View
    {
        $progressLog->load(['creator', 'site', 'evidences.uploader']);

        return view('admin.inventory.progress-detail', compact('progressLog'));
    }

    public function approveProcurement(Request $request, ProcurementRequest $procurementRequest): RedirectResponse
    {
        $validated = $request->validate([
            'po_number' => ['required', 'string', 'max:255'],
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($procurementRequest->status !== 'requested') {
            return back()->with('error', 'Only requested records can be approved.');
        }

        $procurementRequest->update([
            'status' => 'approved',
            'po_number' => $validated['po_number'],
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $this->logAction($request, 'admin.inventory.procurement.approved', 'ProcurementRequest', $procurementRequest->id, [
            'po_number' => $validated['po_number'],
            'approval_notes' => $validated['approval_notes'] ?? null,
        ]);

        return back()->with('success', 'Procurement request approved.');
    }

    public function rejectProcurement(Request $request, ProcurementRequest $procurementRequest): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($procurementRequest->status !== 'requested') {
            return back()->with('error', 'Only requested records can be rejected.');
        }

        $procurementRequest->update([
            'status' => 'rejected',
        ]);

        $this->logAction($request, 'admin.inventory.procurement.rejected', 'ProcurementRequest', $procurementRequest->id, [
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Procurement request rejected.');
    }

    public function deleteProcurement(Request $request, ProcurementRequest $procurementRequest): RedirectResponse
    {
        if (!in_array($procurementRequest->status, ['requested', 'rejected'])) {
            return back()->with('error', 'Can only delete requested or rejected procurement records.');
        }

        $this->logAction($request, 'admin.inventory.procurement.deleted', 'ProcurementRequest', $procurementRequest->id, [
            'status' => $procurementRequest->status,
        ]);

        $procurementRequest->delete();

        return back()->with('success', 'Procurement request deleted.');
    }

    private function logAction(Request $request, string $action, string $entityType, int $entityId, array $meta = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
