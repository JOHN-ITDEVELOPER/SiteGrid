<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\Site;
use App\Services\InventoryTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryCategoryController extends Controller
{
    /**
     * Display inventory categories for owner's site
     */
    public function index(Request $request)
    {
        $siteId = $request->query('site_id');
        $ownerSiteIds = Auth::user()->ownedSites()->pluck('id')->toArray();

        if (!$siteId || !in_array($siteId, $ownerSiteIds)) {
            $siteId = $ownerSiteIds[0] ?? null;
        }

        if (!$siteId) {
            return view('owner.inventory.categories-index', [
                'categories' => [],
                'sites' => [],
                'selectedSite' => null,
                'templates' => [],
            ]);
        }

        $site = Site::find($siteId);
        $categories = $site->inventoryCategories()->with('items')->get();
        $sites = Auth::user()->ownedSites()->get();
        $templates = InventoryTemplateService::getAvailableTemplates();

        return view('owner.inventory.categories-index', [
            'categories' => $categories,
            'sites' => $sites,
            'selectedSite' => $site,
            'templates' => $templates,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $siteId = $request->query('site_id');
        $ownerSiteIds = Auth::user()->ownedSites()->pluck('id')->toArray();

        if (!$siteId || !in_array($siteId, $ownerSiteIds)) {
            $siteId = $ownerSiteIds[0] ?? null;
        }

        if (!$siteId) {
            return redirect()->route('owner.inventory.categories.index')->with('error', 'You have no sites to manage.');
        }

        $site = Site::find($siteId);

        return view('owner.inventory.category-form', [
            'site' => $site,
            'category' => null,
        ]);
    }

    /**
     * Store category
     */
    public function store(Request $request)
    {
        $siteId = (int) $request->input('site_id');
        $ownerSiteIds = Auth::user()->ownedSites()->pluck('id')->toArray();

        if (!$siteId || !in_array($siteId, $ownerSiteIds)) {
            return back()->with('error', 'Invalid site selected.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('inventory_categories')
                    ->where('site_id', $siteId),
            ],
            'type' => 'required|in:material,tool,equipment',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['site_id'] = $siteId;

        try {
            InventoryCategory::create($validated);
            return redirect()->route('owner.inventory.categories.index', ['site_id' => $siteId])
                ->with('success', 'Category created successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, InventoryCategory $category)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds)) {
            abort(403, 'Unauthorized');
        }

        $site = Site::find($category->site_id);

        return view('owner.inventory.category-form', [
            'site' => $site,
            'category' => $category,
        ]);
    }

    /**
     * Update category
     */
    public function update(Request $request, InventoryCategory $category)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds)) {
            abort(403, 'Unauthorized');
        }

        $siteId = $category->site_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('inventory_categories')
                    ->where('site_id', $siteId)
                    ->ignore($category->id),
            ],
            'type' => 'required|in:material,tool,equipment',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $category->update($validated);
            return redirect()->route('owner.inventory.categories.index', ['site_id' => $siteId])
                ->with('success', 'Category updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Delete category
     */
    public function destroy(InventoryCategory $category)
    {
        $ownerSiteIds = Auth::user()->sites()->pluck('sites.id')->toArray();

        if (!in_array($category->site_id, $ownerSiteIds)) {
            abort(403, 'Unauthorized');
        }

        $siteId = $category->site_id;

        // Cascade delete items in this category
        $category->items()->delete();
        $category->delete();

        return redirect()->route('owner.inventory.categories.index', ['site_id' => $siteId])
            ->with('success', 'Category deleted successfully');
    }

    /**
     * Apply a template to the site
     */
    public function applyTemplate(Request $request)
    {
        $siteId = $request->input('site_id');
        $templateKey = $request->input('template');

        $ownerSiteIds = Auth::user()->ownedSites()->pluck('id')->toArray();

        if (!in_array($siteId, $ownerSiteIds)) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'template' => 'required|string',
        ]);

        $site = Site::find($siteId);

        try {
            InventoryTemplateService::applyTemplate($site, $templateKey);

            return redirect()->route('owner.inventory.categories.index', ['site_id' => $siteId])
                ->with('success', 'Template applied successfully. You can now customize categories and items.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
