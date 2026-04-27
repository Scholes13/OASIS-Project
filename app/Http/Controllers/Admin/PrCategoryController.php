<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\PurchaseRequest\PrCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = PrCategory::withCount('purchaseRequests as usage_count')
            ->ordered()
            ->paginate(15);

        return Inertia::render('Admin/PrCategories/Index', [
            'categories' => [
                'data' => $categories->items(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed for inline forms - handled by index page
        return redirect()->route('admin.pr-categories.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:pr_categories,code',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $request->input('sort_order', 0);

        PrCategory::create($validated);

        return redirect()->route('admin.pr-categories.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(PrCategory $prCategory)
    {
        // Not needed for inline forms - handled by index page
        return redirect()->route('admin.pr-categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PrCategory $prCategory)
    {
        // Not needed for inline forms - handled by index page
        return redirect()->route('admin.pr-categories.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PrCategory $prCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:pr_categories,code,'.$prCategory->id,
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $prCategory->update($validated);

        return redirect()->route('admin.pr-categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PrCategory $prCategory)
    {
        // Check if category is being used
        $usageCount = $prCategory->purchaseRequests()->count();

        if ($usageCount > 0) {
            return redirect()->route('admin.pr-categories.index')
                ->withErrors(['delete' => "Cannot delete category that is being used by {$usageCount} purchase request(s)."]);
        }

        $prCategory->delete();

        return redirect()->route('admin.pr-categories.index');
    }
}
