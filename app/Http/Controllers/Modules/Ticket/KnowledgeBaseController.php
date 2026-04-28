<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreKnowledgeArticleRequest;
use App\Http\Requests\Ticket\UpdateKnowledgeArticleRequest;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Ticket\KnowledgeArticle;
use App\Models\Modules\Ticket\KnowledgeCategory;
use App\Models\Modules\Ticket\Ticket;
use App\Services\Modules\Ticket\KnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        private KnowledgeBaseService $knowledgeBaseService,
    ) {}

    // ==================== PUBLIC (ALL USERS) ====================

    /**
     * KB home — browse categories and popular articles.
     *
     * GET /it-support/knowledge-base
     */
    public function browse(Request $request): Response
    {
        $buId = (int) session('current_business_unit_id');

        $categoryTree = $this->knowledgeBaseService->getCategoryTree($buId);

        $popularArticles = KnowledgeArticle::where('business_unit_id', $buId)
            ->where('is_published', true)
            ->with('category')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();

        $recentArticles = KnowledgeArticle::where('business_unit_id', $buId)
            ->where('is_published', true)
            ->with('category')
            ->latest('published_at')
            ->limit(10)
            ->get();

        return Inertia::render('Ticket/Knowledge/Browse', [
            'categories' => $categoryTree,
            'popularArticles' => $popularArticles,
            'recentArticles' => $recentArticles,
        ]);
    }

    /**
     * Search published articles.
     *
     * GET /it-support/knowledge-base/search
     */
    public function search(Request $request): Response
    {
        $buId = (int) session('current_business_unit_id');
        $query = $request->get('q', '');

        $articles = $query
            ? $this->knowledgeBaseService->searchArticles($buId, $query)
            : collect();

        return Inertia::render('Ticket/Knowledge/Search', [
            'articles' => $articles,
            'query' => $query,
        ]);
    }

    /**
     * Show a single published article and track the view.
     *
     * GET /it-support/knowledge-base/articles/{slug}
     */
    public function article(Request $request, string $slug): Response
    {
        $buId = (int) session('current_business_unit_id');

        $article = KnowledgeArticle::where('business_unit_id', $buId)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['category', 'author'])
            ->firstOrFail();

        // Track the view
        $this->knowledgeBaseService->trackView($article, $request, $request->user());

        // Get related articles from the same category
        $relatedArticles = collect();
        if ($article->category_id) {
            $relatedArticles = KnowledgeArticle::where('business_unit_id', $buId)
                ->where('category_id', $article->category_id)
                ->where('id', '!=', $article->id)
                ->where('is_published', true)
                ->orderByDesc('views_count')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'views_count']);
        }

        return Inertia::render('Ticket/Knowledge/Article', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
        ]);
    }

    /**
     * Suggest articles based on ticket title (JSON response for ticket form).
     *
     * GET /it-support/knowledge-base/suggest
     */
    public function suggestArticles(Request $request): JsonResponse
    {
        $buId = (int) session('current_business_unit_id');
        $title = $request->get('title', '');

        if (strlen($title) < 3) {
            return response()->json([]);
        }

        $articles = $this->knowledgeBaseService->suggestArticles($buId, $title);

        return response()->json($articles->map(fn (KnowledgeArticle $article): array => [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'category' => $article->category?->name,
        ]));
    }

    // ==================== ADMIN (IT SUPPORT ADMIN) ====================

    /**
     * List all articles (admin view, includes unpublished).
     *
     * GET /it-support/knowledge-base/admin
     */
    public function adminIndex(Request $request): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $filters = [
            'search' => $request->get('search', ''),
            'category_id' => $request->get('category_id', ''),
            'is_published' => $request->get('is_published', ''),
        ];

        $query = KnowledgeArticle::whereIn('business_unit_id', $scopedBuIds)
            ->with(['category', 'author']);

        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        if ($filters['is_published'] !== '') {
            $query->where('is_published', $filters['is_published'] === '1');
        }

        $articles = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = KnowledgeCategory::whereIn('business_unit_id', $scopedBuIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ticket/Knowledge/Index', [
            'articles' => $articles,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the create article form.
     *
     * GET /it-support/knowledge-base/admin/create
     */
    public function adminCreate(): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $categories = KnowledgeCategory::whereIn('business_unit_id', $scopedBuIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ticket/Knowledge/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new article.
     *
     * POST /it-support/knowledge-base/admin
     */
    public function adminStore(StoreKnowledgeArticleRequest $request): RedirectResponse
    {
        $buId = (int) session('current_business_unit_id');

        try {
            $article = $this->knowledgeBaseService->createArticle(
                $request->validated(),
                $request->user(),
                $buId
            );

            return redirect()->route('it-support.admin.knowledge.index')
                ->with('success', 'Artikel berhasil dibuat.');
        } catch (\Exception $e) {
            Log::error('Failed to create knowledge article', [
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()
                ->with('error', 'Gagal membuat artikel. Silakan coba lagi.');
        }
    }

    /**
     * Show the edit article form.
     *
     * GET /it-support/knowledge-base/admin/{article}/edit
     */
    public function adminEdit(KnowledgeArticle $article): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $article->business_unit_id, $scopedBuIds, true), 403);

        $article->load('category');

        $categories = KnowledgeCategory::whereIn('business_unit_id', $scopedBuIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ticket/Knowledge/Edit', [
            'article' => $article,
            'categories' => $categories,
        ]);
    }

    /**
     * Update an article.
     *
     * PUT /it-support/knowledge-base/admin/{article}
     */
    public function adminUpdate(UpdateKnowledgeArticleRequest $request, KnowledgeArticle $article): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $article->business_unit_id, $scopedBuIds, true), 403);

        try {
            $this->knowledgeBaseService->updateArticle($article, $request->validated());

            return redirect()->route('it-support.admin.knowledge.index')
                ->with('success', 'Artikel berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Failed to update knowledge article', [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()
                ->with('error', 'Gagal memperbarui artikel. Silakan coba lagi.');
        }
    }

    /**
     * Delete an article.
     *
     * DELETE /it-support/knowledge-base/admin/{article}
     */
    public function adminDestroy(KnowledgeArticle $article): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $article->business_unit_id, $scopedBuIds, true), 403);

        try {
            $this->knowledgeBaseService->deleteArticle($article);

            return redirect()->route('it-support.admin.knowledge.index')
                ->with('success', 'Artikel berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete knowledge article', [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus artikel. Silakan coba lagi.');
        }
    }

    /**
     * Link a knowledge article to a ticket.
     *
     * POST /it-support/tickets/{ticket}/link-article
     */
    public function linkArticle(Request $request, Ticket $ticket): RedirectResponse
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();
        abort_unless(in_array((int) $ticket->business_unit_id, $scopedBuIds, true), 403);

        $request->validate([
            'article_id' => ['required', 'integer', 'exists:ticket_knowledge_articles,id'],
        ]);

        try {
            $this->knowledgeBaseService->linkArticleToTicket($ticket, $request->integer('article_id'));

            return back()->with('success', 'Artikel berhasil ditautkan ke ticket.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
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
