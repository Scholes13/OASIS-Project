<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreCategoryRequest;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Ticket\TicketCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketCategoryController extends Controller
{
    /**
     * List all ticket categories.
     *
     * GET /it-support/categories
     */
    public function index(): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $categories = TicketCategory::whereIn('business_unit_id', $scopedBuIds)
            ->withCount('tickets')
            ->orderBy('name')
            ->get();

        return Inertia::render('Ticket/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the create category form.
     *
     * GET /it-support/categories/create
     */
    public function create(): Response
    {
        return Inertia::render('Ticket/Categories/Create');
    }

    /**
     * Store a new ticket category.
     *
     * POST /it-support/categories
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $buId = (int) session('current_business_unit_id');

        TicketCategory::create([
            'business_unit_id' => $buId,
            'name' => $request->validated()['name'],
            'description' => $request->validated()['description'] ?? null,
            'color' => $request->validated()['color'] ?? null,
            'is_active' => $request->validated()['is_active'] ?? true,
        ]);

        return redirect()->route('it-support.admin.categories.index')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    /**
     * Show the edit category form.
     *
     * GET /it-support/categories/{category}/edit
     */
    public function edit(TicketCategory $category): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $category->business_unit_id, $scopedBuIds, true), 403);

        return Inertia::render('Ticket/Categories/Edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update a ticket category.
     *
     * PUT /it-support/categories/{category}
     */
    public function update(StoreCategoryRequest $request, TicketCategory $category): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $category->business_unit_id, $scopedBuIds, true), 403);

        $category->update($request->validated());

        return redirect()->route('it-support.admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Delete a ticket category.
     *
     * DELETE /it-support/categories/{category}
     */
    public function destroy(TicketCategory $category): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $category->business_unit_id, $scopedBuIds, true), 403);

        // Prevent deletion if category has tickets
        if ($category->tickets()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki ticket.');
        }

        $category->delete();

        return redirect()->route('it-support.admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Resolve the active BU scope for IT Support admin.
     * Parent or holding BUs include all descendants for roll-up views.
     *
     * @return array<int>
     */
    private function resolveScopedBusinessUnitIds(): array
    {
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        if ($currentBusinessUnitId <= 0) {
            return [];
        }

        $currentBusinessUnit = BusinessUnit::with('descendants')->find($currentBusinessUnitId);

        if (! $currentBusinessUnit) {
            return [$currentBusinessUnitId];
        }

        return $currentBusinessUnit->getAccessibleBusinessUnits();
    }
}
