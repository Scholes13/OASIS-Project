<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Ticket\KnowledgeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeCategoryController extends Controller
{
    /**
     * List all knowledge categories.
     *
     * GET /it-support/knowledge-categories
     */
    public function index(): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $categories = KnowledgeCategory::whereIn('business_unit_id', $scopedBuIds)
            ->with('parent')
            ->withCount(['articles', 'publishedArticles'])
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Ticket/Knowledge/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the create category form.
     *
     * GET /it-support/knowledge-categories/create
     */
    public function create(): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $parentCategories = KnowledgeCategory::whereIn('business_unit_id', $scopedBuIds)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ticket/Knowledge/Categories/Create', [
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Store a new knowledge category.
     *
     * POST /it-support/knowledge-categories
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:ticket_knowledge_categories,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $buId = (int) session('current_business_unit_id');

        KnowledgeCategory::create([
            'business_unit_id' => $buId,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()->route('it-support.admin.knowledge.categories.index')
            ->with('success', 'Kategori knowledge base berhasil dibuat.');
    }

    /**
     * Show the edit category form.
     *
     * GET /it-support/knowledge-categories/{knowledgeCategory}/edit
     */
    public function edit(KnowledgeCategory $knowledgeCategory): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $knowledgeCategory->business_unit_id, $scopedBuIds, true), 403);

        $parentCategories = KnowledgeCategory::whereIn('business_unit_id', $scopedBuIds)
            ->whereNull('parent_id')
            ->where('id', '!=', $knowledgeCategory->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ticket/Knowledge/Categories/Edit', [
            'knowledgeCategory' => $knowledgeCategory,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Update a knowledge category.
     *
     * PUT /it-support/knowledge-categories/{knowledgeCategory}
     */
    public function update(Request $request, KnowledgeCategory $knowledgeCategory): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $knowledgeCategory->business_unit_id, $scopedBuIds, true), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:ticket_knowledge_categories,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'order' => $validated['order'] ?? 0,
        ];

        // Regenerate slug if name changed
        if ($validated['name'] !== $knowledgeCategory->name) {
            $updateData['slug'] = Str::slug($validated['name']);
        }

        // Prevent self-referencing parent
        if (isset($validated['parent_id']) && $validated['parent_id'] === $knowledgeCategory->id) {
            return back()->with('error', 'Kategori tidak dapat menjadi parent dari dirinya sendiri.');
        }

        $knowledgeCategory->update($updateData);

        return redirect()->route('it-support.admin.knowledge.categories.index')
            ->with('success', 'Kategori knowledge base berhasil diperbarui.');
    }

    /**
     * Delete a knowledge category.
     *
     * DELETE /it-support/knowledge-categories/{knowledgeCategory}
     */
    public function destroy(KnowledgeCategory $knowledgeCategory): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $knowledgeCategory->business_unit_id, $scopedBuIds, true), 403);

        // Prevent deletion if category has articles
        if ($knowledgeCategory->articles()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki artikel.');
        }

        // Prevent deletion if category has children
        if ($knowledgeCategory->children()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki sub-kategori.');
        }

        $knowledgeCategory->delete();

        return redirect()->route('it-support.admin.knowledge.categories.index')
            ->with('success', 'Kategori knowledge base berhasil dihapus.');
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
