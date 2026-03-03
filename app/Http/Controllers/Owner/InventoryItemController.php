<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryItemController extends Controller
{
    /**
     * Display items in a category
     */
    public function index(Request $request, InventoryCategory $category)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds)) {
            abort(403, 'Unauthorized');
        }

        $site = Site::find($category->site_id);
        $items = $category->items()->get();

        return view('owner.inventory.items-index', [
            'category' => $category,
            'site' => $site,
            'items' => $items,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request, InventoryCategory $category)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds)) {
            abort(403, 'Unauthorized');
        }

        return view('owner.inventory.item-form', [
            'category' => $category,
            'item' => null,
        ]);
    }

    /**
     * Store item
     */
    public function store(Request $request, InventoryCategory $category)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds)) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name,NULL,id,site_id,' . $category->site_id . ',category_id,' . $category->id,
            'sku' => 'nullable|string|max:100|unique:inventory_items,sku,NULL,id,site_id,' . $category->site_id,
            'unit' => 'required|string|max:50',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['site_id'] = $category->site_id;
        $validated['category_id'] = $category->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        InventoryItem::create($validated);

        return redirect()->route('owner.inventory.items.index', $category->id)
            ->with('success', 'Item created successfully');
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, InventoryCategory $category, InventoryItem $item)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds) || $item->category_id !== $category->id) {
            abort(403, 'Unauthorized');
        }

        return view('owner.inventory.item-form', [
            'category' => $category,
            'item' => $item,
        ]);
    }

    /**
     * Update item
     */
    public function update(Request $request, InventoryCategory $category, InventoryItem $item)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds) || $item->category_id !== $category->id) {
            abort(403, 'Unauthorized');
        }

        $siteId = $category->site_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name,' . $item->id . ',id,site_id,' . $siteId . ',category_id,' . $category->id,
            'sku' => 'nullable|string|max:100|unique:inventory_items,sku,' . $item->id . ',id,site_id,' . $siteId,
            'unit' => 'required|string|max:50',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $item->update($validated);

        return redirect()->route('owner.inventory.items.index', $category->id)
            ->with('success', 'Item updated successfully');
    }

    /**
     * Delete item
     */
    public function destroy(InventoryCategory $category, InventoryItem $item)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds) || $item->category_id !== $category->id) {
            abort(403, 'Unauthorized');
        }

        $item->delete();

        return redirect()->route('owner.inventory.items.index', $category->id)
            ->with('success', 'Item deleted successfully');
    }
}
