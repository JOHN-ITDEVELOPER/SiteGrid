<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryCategory;
use App\Models\InventoryEvidence;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\SiteProgressLog;
use App\Services\InventoryLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    private InventoryLedgerService $ledgerService;

    public function __construct(InventoryLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request): View
    {
        $siteIds = auth()->user()->ownedSites()->pluck('id');
        $siteId = (int) ($request->input('site_id', $siteIds->first() ?? 0));

        $stocks = collect();
        $pendingRequests = collect();
        $recentMovements = collect();
        $recentItems = collect();
        $items = collect();
        $progressLogs = collect();
        $lowStockCount = 0;
        $categoryCount = 0;
        $activeItemCount = 0;

        if ($siteId && $siteIds->contains($siteId)) {
            $stocks = InventoryStock::with('item.category')
                ->where('site_id', $siteId)
                ->orderByDesc('updated_at')
                ->get();

            $pendingRequests = ProcurementRequest::with(['requester', 'items.item'])
                ->where('site_id', $siteId)
                ->whereIn('status', ['requested', 'approved', 'po_issued'])
                ->latest()
                ->get();

            $recentMovements = InventoryMovement::with(['item', 'performedBy', 'evidences'])
                ->where('site_id', $siteId)
                ->latest()
                ->limit(20)
                ->get();

            $recentItems = InventoryItem::with('category')
                ->where('site_id', $siteId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $items = InventoryItem::with('category')
                ->where('site_id', $siteId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $lowStockCount = InventoryStock::where('site_id', $siteId)
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->where('low_stock_threshold', '>', 0)
                ->count();

            $categoryCount = InventoryCategory::where('site_id', $siteId)->count();

            $activeItemCount = InventoryItem::where('site_id', $siteId)
                ->where('is_active', true)
                ->count();

            $progressLogs = SiteProgressLog::with('creator')
                ->where('site_id', $siteId)
                ->latest('log_date')
                ->limit(10)
                ->get();
        }

        $sites = auth()->user()->ownedSites()->select('id', 'name')->orderBy('name')->get();

        return view('owner.inventory.index', compact(
            'sites',
            'siteId',
            'stocks',
            'pendingRequests',
            'recentMovements',
            'recentItems',
            'items',
            'progressLogs',
            'lowStockCount',
            'categoryCount',
            'activeItemCount'
        ));
    }

    public function directStockIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'item_id' => ['required', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:1000'],
            'evidences' => ['required', 'array', 'min:1'],
            'evidences.*' => ['required', 'image', 'max:5120'],
        ]);

        $this->assertOwnerHasSite((int) $validated['site_id']);

        $item = InventoryItem::query()
            ->where('id', (int) $validated['item_id'])
            ->where('site_id', (int) $validated['site_id'])
            ->first();

        if (!$item) {
            return back()->with('error', 'Selected item does not belong to the selected site.');
        }

        $movement = $this->ledgerService->recordMovement([
            'site_id' => (int) $validated['site_id'],
            'item_id' => (int) $validated['item_id'],
            'movement_type' => 'adjustment_in',
            'quantity' => (float) $validated['quantity'],
            'unit_cost' => $validated['unit_cost'] ?? null,
            'reference' => $validated['reference'],
            'notes' => $validated['notes'],
            'performed_by' => auth()->id(),
        ]);

        foreach ($request->file('evidences', []) as $image) {
            $path = $image->store('inventory/evidence', 'public');
            InventoryEvidence::create([
                'site_id' => (int) $validated['site_id'],
                'evidenceable_type' => InventoryMovement::class,
                'evidenceable_id' => $movement->id,
                'file_path' => $path,
                'caption' => 'Direct stock-in: ' . $validated['reference'],
                'uploaded_by' => auth()->id(),
            ]);
        }

        $this->logAction($request, 'owner.inventory.direct-stock-in.logged', 'InventoryMovement', $movement->id, [
            'item_id' => (int) $validated['item_id'],
            'quantity' => (float) $validated['quantity'],
            'reference' => $validated['reference'],
        ]);

        return back()->with('success', 'Direct stock-in recorded successfully.');
    }

    public function approve(Request $request, ProcurementRequest $procurementRequest): RedirectResponse
    {
        $this->assertOwnerHasSite($procurementRequest->site_id);

        $validated = $request->validate([
            'po_number' => ['required', 'string', 'max:255'],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
            'evidences' => ['required', 'array', 'min:1'],
            'evidences.*' => ['required', 'image', 'max:5120'],
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

        foreach ($procurementRequest->items as $item) {
            if ($item->approved_quantity === null) {
                $item->approved_quantity = $item->requested_quantity;
                $item->save();
            }
        }

        foreach ($request->file('evidences', []) as $image) {
            $path = $image->store('inventory/evidence', 'public');
            InventoryEvidence::create([
                'site_id' => $procurementRequest->site_id,
                'evidenceable_type' => ProcurementRequest::class,
                'evidenceable_id' => $procurementRequest->id,
                'file_path' => $path,
                'caption' => $validated['decision_notes'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);
        }

        $this->logAction($request, 'owner.inventory.procurement.approved', 'ProcurementRequest', $procurementRequest->id, [
            'po_number' => $validated['po_number'],
        ]);

        return back()->with('success', 'Procurement request approved.');
    }

    public function reject(Request $request, ProcurementRequest $procurementRequest): RedirectResponse
    {
        $this->assertOwnerHasSite($procurementRequest->site_id);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'evidences' => ['required', 'array', 'min:1'],
            'evidences.*' => ['required', 'image', 'max:5120'],
        ]);

        if ($procurementRequest->status !== 'requested') {
            return back()->with('error', 'Only requested records can be rejected.');
        }

        $procurementRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        foreach ($request->file('evidences', []) as $image) {
            $path = $image->store('inventory/evidence', 'public');
            InventoryEvidence::create([
                'site_id' => $procurementRequest->site_id,
                'evidenceable_type' => ProcurementRequest::class,
                'evidenceable_id' => $procurementRequest->id,
                'file_path' => $path,
                'caption' => $validated['rejection_reason'],
                'uploaded_by' => auth()->id(),
            ]);
        }

        $this->logAction($request, 'owner.inventory.procurement.rejected', 'ProcurementRequest', $procurementRequest->id, [
            'reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Procurement request rejected.');
    }

    public function receive(Request $request, ProcurementRequest $procurementRequest): RedirectResponse
    {
        $this->assertOwnerHasSite($procurementRequest->site_id);

        $validated = $request->validate([
            'delivery_reference' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.delivered_quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'evidences' => ['required', 'array', 'min:1'],
            'evidences.*' => ['required', 'image', 'max:5120'],
        ]);

        if (!in_array($procurementRequest->status, ['approved', 'po_issued'], true)) {
            return back()->with('error', 'Only approved requests can be received.');
        }

        foreach ($validated['items'] as $entry) {
            $movement = $this->ledgerService->recordMovement([
                'site_id' => $procurementRequest->site_id,
                'item_id' => (int) $entry['item_id'],
                'movement_type' => 'procurement_in',
                'quantity' => (float) $entry['delivered_quantity'],
                'unit_cost' => $entry['unit_cost'] ?? null,
                'procurement_request_id' => $procurementRequest->id,
                'reference' => $validated['delivery_reference'],
                'notes' => $validated['notes'] ?? null,
                'performed_by' => auth()->id(),
            ]);

            foreach ($request->file('evidences', []) as $image) {
                $path = $image->store('inventory/evidence', 'public');
                InventoryEvidence::create([
                    'site_id' => $procurementRequest->site_id,
                    'evidenceable_type' => InventoryMovement::class,
                    'evidenceable_id' => $movement->id,
                    'file_path' => $path,
                    'caption' => 'Delivery evidence ' . $validated['delivery_reference'],
                    'uploaded_by' => auth()->id(),
                ]);
            }

            $requestItem = $procurementRequest->items->firstWhere('item_id', (int) $entry['item_id']);
            if ($requestItem) {
                $requestItem->update([
                    'delivered_quantity' => (float) $entry['delivered_quantity'],
                    'final_unit_cost' => $entry['unit_cost'] ?? null,
                ]);
            }
        }

        $procurementRequest->update([
            'status' => 'received',
            'delivered_at' => now(),
        ]);

        $this->logAction($request, 'owner.inventory.procurement.received', 'ProcurementRequest', $procurementRequest->id, [
            'delivery_reference' => $validated['delivery_reference'],
            'line_items' => count($validated['items']),
        ]);

        return back()->with('success', 'Delivery received and stock updated.');
    }

    public function deleteProcurement(Request $request, ProcurementRequest $procurementRequest): RedirectResponse
    {
        $this->assertOwnerHasSite($procurementRequest->site_id);

        if (!in_array($procurementRequest->status, ['requested', 'rejected'])) {
            return back()->with('error', 'Can only delete requested or rejected procurement records.');
        }

        $this->logAction($request, 'owner.inventory.procurement.deleted', 'ProcurementRequest', $procurementRequest->id, [
            'status' => $procurementRequest->status,
        ]);

        $procurementRequest->delete();

        return back()->with('success', 'Procurement request deleted.');
    }

    public function showProgress(SiteProgressLog $progressLog): View
    {
        $this->assertOwnerHasSite($progressLog->site_id);

        $progressLog->load(['creator', 'site', 'evidences.uploader']);

        return view('owner.inventory.progress-detail', compact('progressLog'));
    }

    public function updateProgressStatus(Request $request, SiteProgressLog $progressLog): RedirectResponse
    {
        $this->assertOwnerHasSite($progressLog->site_id);

        $validated = $request->validate([
            'status' => ['required', 'in:submitted,approved,reviewed'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $progressLog->update([
            'status' => $validated['status'],
        ]);

        $this->logAction($request, 'owner.inventory.progress.status-updated', 'SiteProgressLog', $progressLog->id, [
            'status' => $validated['status'],
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Progress log status updated to ' . $validated['status'] . '.');
    }

    private function assertOwnerHasSite(int $siteId): void
    {
        if (!auth()->user()->ownedSites()->where('id', $siteId)->exists()) {
            abort(403, 'Unauthorized site access.');
        }
    }

    public function updateThreshold(Request $request, InventoryStock $inventoryStock): RedirectResponse
    {
        $siteId = (int) auth()->user()->ownedSites()->pluck('id')->first();
        
        $this->assertOwnerHasSite($inventoryStock->site_id, $siteId);

        $validated = $request->validate([
            'low_stock_threshold' => 'required|numeric|min:0',
        ]);

        $inventoryStock->update([
            'low_stock_threshold' => (float) $validated['low_stock_threshold'],
        ]);

        $this->logAction(
            $request,
            'owner.inventory.threshold-updated',
            'InventoryStock',
            $inventoryStock->id,
            [
                'item_id' => $inventoryStock->item_id,
                'new_threshold' => $validated['low_stock_threshold'],
            ]
        );

        return back()->with('success', 'Reorder point updated successfully.');
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
