<?php

namespace App\Http\Controllers\Foreman;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryCategory;
use App\Models\InventoryEvidence;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\Site;
use App\Models\SiteMember;
use App\Models\SiteProgressLog;
use App\Models\SiteWorker;
use App\Services\InventoryLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class InventoryController extends Controller
{
    private InventoryLedgerService $ledgerService;

    public function __construct(InventoryLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request): View
    {
        $user = auth()->user();
        $foremanSiteIds = $this->foremanSiteIds($user->id);
        $selectedSiteId = (int) ($request->input('site_id', $foremanSiteIds->first() ?? 0));

        $sites = Site::whereIn('id', $foremanSiteIds)->select('id', 'name')->orderBy('name')->get();

        $stocks = collect();
        $requests = collect();
        $movements = collect();
        $progressLogs = collect();
        $recentItems = collect();
        $lowStockCount = 0;
        $categoryCount = 0;

        if ($selectedSiteId && $foremanSiteIds->contains($selectedSiteId)) {
            $stocks = InventoryStock::with('item.category')
                ->where('site_id', $selectedSiteId)
                ->orderByDesc('updated_at')
                ->get();

            $requests = ProcurementRequest::with(['requester', 'items.item'])
                ->where('site_id', $selectedSiteId)
                ->latest()
                ->limit(12)
                ->get();

            $movements = InventoryMovement::with(['item', 'performedBy'])
                ->where('site_id', $selectedSiteId)
                ->latest()
                ->limit(15)
                ->get();

            $progressLogs = SiteProgressLog::with('creator')
                ->where('site_id', $selectedSiteId)
                ->latest('log_date')
                ->limit(10)
                ->get();

            $recentItems = InventoryItem::with('category')
                ->where('site_id', $selectedSiteId)
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $lowStockCount = InventoryStock::where('site_id', $selectedSiteId)
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->where('low_stock_threshold', '>', 0)
                ->count();

            $categoryCount = InventoryCategory::where('site_id', $selectedSiteId)->count();
        }

        $myRequests = ProcurementRequest::with('items.item')
            ->whereIn('site_id', $foremanSiteIds)
            ->where('requested_by', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $myUsage = InventoryMovement::with(['item', 'evidences'])
            ->whereIn('site_id', $foremanSiteIds)
            ->where('performed_by', $user->id)
            ->where('movement_type', 'usage_out')
            ->latest()
            ->limit(5)
            ->get();

        $myProgressLogs = SiteProgressLog::with('site')
            ->whereIn('site_id', $foremanSiteIds)
            ->where('created_by', $user->id)
            ->latest('log_date')
            ->limit(5)
            ->get();

        $items = InventoryItem::with('category')
                ->where('site_id', $selectedSiteId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

        return view('field.inventory.index', compact(
            'foremanSiteIds',
            'sites',
            'selectedSiteId',
            'stocks',
            'requests',
            'movements',
            'progressLogs',
            'lowStockCount',
            'categoryCount',
            'recentItems',
            'items',
            'myRequests',
            'myUsage',
            'myProgressLogs'
        ));
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'purpose' => ['required', 'string', 'max:1000'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.requested_quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'evidences' => ['required', 'array', 'min:1'],
            'evidences.*' => ['required', 'image', 'max:5120'],
        ]);

        $user = auth()->user();
        $siteId = (int) $validated['site_id'];
        $this->assertForemanSite($user->id, $siteId);

        $reference = 'PR-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

        $procurementRequest = ProcurementRequest::create([
            'site_id' => $siteId,
            'reference' => $reference,
            'status' => 'requested',
            'purpose' => $validated['purpose'],
            'supplier_name' => $validated['supplier_name'] ?? null,
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'requested_by' => $user->id,
        ]);

        foreach ($validated['items'] as $item) {
            $procurementRequest->items()->create([
                'item_id' => $item['item_id'],
                'requested_quantity' => $item['requested_quantity'],
                'estimated_unit_cost' => $item['estimated_unit_cost'] ?? null,
            ]);
        }

        foreach ($request->file('evidences', []) as $image) {
            $path = $image->store('inventory/evidence', 'public');
            InventoryEvidence::create([
                'site_id' => $siteId,
                'evidenceable_type' => ProcurementRequest::class,
                'evidenceable_id' => $procurementRequest->id,
                'file_path' => $path,
                'uploaded_by' => $user->id,
            ]);
        }

        $this->logAction($request, 'foreman.inventory.procurement.requested', 'ProcurementRequest', $procurementRequest->id, [
            'reference' => $reference,
            'items_count' => count($validated['items']),
        ]);

        return back()->with('success', 'Procurement request submitted successfully.');
    }

    public function storeUsage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'item_id' => ['required', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['required', 'string', 'max:1000'],
            'evidences' => ['required', 'array', 'min:1'],
            'evidences.*' => ['required', 'image', 'max:5120'],
        ]);

        $user = auth()->user();
        $siteId = (int) $validated['site_id'];
        $this->assertForemanSite($user->id, $siteId);

        try {
            $movement = $this->ledgerService->recordMovement([
                'site_id' => $siteId,
                'item_id' => (int) $validated['item_id'],
                'movement_type' => 'usage_out',
                'quantity' => (float) $validated['quantity'],
                'notes' => $validated['notes'],
                'performed_by' => $user->id,
                'reference' => 'USAGE-' . now()->format('YmdHis'),
            ]);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        foreach ($request->file('evidences', []) as $image) {
            $path = $image->store('inventory/evidence', 'public');
            InventoryEvidence::create([
                'site_id' => $siteId,
                'evidenceable_type' => InventoryMovement::class,
                'evidenceable_id' => $movement->id,
                'file_path' => $path,
                'uploaded_by' => $user->id,
            ]);
        }

        $this->logAction($request, 'foreman.inventory.usage.logged', 'InventoryMovement', $movement->id, [
            'item_id' => $validated['item_id'],
            'quantity' => $validated['quantity'],
        ]);

        return back()->with('success', 'Stock usage recorded successfully.');
    }

    public function storeProgress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'log_date' => ['required', 'date', 'before_or_equal:today'],
            'sector' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:3000'],
            'evidences' => ['required', 'array', 'min:1'],
            'evidences.*' => ['required', 'image', 'max:5120'],
        ]);

        $user = auth()->user();
        $siteId = (int) $validated['site_id'];
        $this->assertForemanSite($user->id, $siteId);

        $progressLog = SiteProgressLog::create([
            'site_id' => $siteId,
            'log_date' => $validated['log_date'],
            'sector' => $validated['sector'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => 'submitted',
            'created_by' => $user->id,
        ]);

        foreach ($request->file('evidences', []) as $image) {
            $path = $image->store('inventory/evidence', 'public');
            InventoryEvidence::create([
                'site_id' => $siteId,
                'evidenceable_type' => SiteProgressLog::class,
                'evidenceable_id' => $progressLog->id,
                'file_path' => $path,
                'uploaded_by' => $user->id,
            ]);
        }

        $this->logAction($request, 'foreman.progress.logged', 'SiteProgressLog', $progressLog->id, [
            'log_date' => $validated['log_date'],
            'sector' => $validated['sector'] ?? null,
        ]);

        return back()->with('success', 'Site progress log submitted successfully.');
    }

    private function foremanSiteIds(int $userId)
    {
        $fromSiteWorkers = SiteWorker::where('user_id', $userId)
            ->where('is_foreman', true)
            ->whereNull('ended_at')
            ->pluck('site_id');

        $fromSiteMembers = SiteMember::where('user_id', $userId)
            ->where('role', 'foreman')
            ->pluck('site_id');

        return $fromSiteWorkers->merge($fromSiteMembers)->unique()->values();
    }

    private function assertForemanSite(int $userId, int $siteId): void
    {
        if (!$this->foremanSiteIds($userId)->contains($siteId)) {
            abort(403, 'Unauthorized for this site.');
        }
    }

    public function showProgress(SiteProgressLog $progressLog): View
    {
        $this->assertForemanSite(auth()->id(), $progressLog->site_id);
        $progressLog->load(['creator', 'site', 'evidences.uploader']);

        return view('field.inventory.progress-detail', compact('progressLog'));
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
