<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\PurchaseRequest\PrCategory;
use Illuminate\Http\Request;

class PrCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = PrCategory::ordered()->paginate(10);

        return view('admin.pr-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pr-categories.create');
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

        return redirect()->route('admin.pr-categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PrCategory $prCategory)
    {
        return view('admin.pr-categories.show', compact('prCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PrCategory $prCategory)
    {
        return view('admin.pr-categories.edit', compact('prCategory'));
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

        return redirect()->route('admin.pr-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PrCategory $prCategory)
    {
        // Check if category is being used
        if ($prCategory->purchaseRequests()->exists()) {
            return redirect()->route('admin.pr-categories.index')
                ->with('error', 'Cannot delete category that is being used by purchase requests.');
        }

        $prCategory->delete();

        return redirect()->route('admin.pr-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
