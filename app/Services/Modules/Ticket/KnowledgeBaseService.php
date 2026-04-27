<?php

namespace App\Services\Modules\Ticket;

use App\Models\Core\User;
use App\Models\Modules\Ticket\ArticleView;
use App\Models\Modules\Ticket\KnowledgeArticle;
use App\Models\Modules\Ticket\KnowledgeCategory;
use App\Models\Modules\Ticket\Ticket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeBaseService
{
    /**
     * Create a new knowledge base article.
     *
     * @throws Exception
     */
    public function createArticle(array $data, User $author, int $buId): KnowledgeArticle
    {
        $slug = $this->generateSlug($data['title']);

        return KnowledgeArticle::create([
            'business_unit_id' => $buId,
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'] ?? '',
            'category_id' => $data['category_id'] ?? null,
            'is_published' => $data['is_published'] ?? false,
            'author_id' => $author->id,
            'published_at' => ! empty($data['is_published']) ? now() : null,
            'meta_description' => $data['meta_description'] ?? null,
            'tags' => $data['tags'] ?? [],
        ]);
    }

    /**
     * Update an existing knowledge base article.
     */
    public function updateArticle(KnowledgeArticle $article, array $data): KnowledgeArticle
    {
        $updateData = [
            'title' => $data['title'] ?? $article->title,
            'content' => $data['content'] ?? $article->content,
            'category_id' => $data['category_id'] ?? $article->category_id,
            'meta_description' => $data['meta_description'] ?? $article->meta_description,
            'tags' => $data['tags'] ?? $article->tags,
        ];

        // Regenerate slug if title changed
        if (isset($data['title']) && $data['title'] !== $article->title) {
            $updateData['slug'] = $this->generateSlug($data['title']);
        }

        // Handle publish state transitions
        if (isset($data['is_published'])) {
            $updateData['is_published'] = $data['is_published'];

            if ($data['is_published'] && ! $article->is_published) {
                $updateData['published_at'] = now();
            }
        }

        $article->update($updateData);

        return $article->fresh();
    }

    /**
     * Delete a knowledge base article.
     */
    public function deleteArticle(KnowledgeArticle $article): void
    {
        // Detach from any linked tickets
        $article->tickets()->detach();

        // Delete view records
        $article->views()->delete();

        $article->delete();
    }

    /**
     * Generate a unique slug from a title.
     */
    public function generateSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (KnowledgeArticle::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Search published articles by query string (full-text).
     */
    public function searchArticles(int $buId, string $query): Collection
    {
        $searchTerms = '%'.trim($query).'%';

        return KnowledgeArticle::where('business_unit_id', $buId)
            ->where('is_published', true)
            ->where(function ($q) use ($searchTerms) {
                $q->where('title', 'like', $searchTerms)
                    ->orWhere('content', 'like', $searchTerms)
                    ->orWhere('meta_description', 'like', $searchTerms);
            })
            ->with('category')
            ->orderByDesc('views_count')
            ->limit(20)
            ->get();
    }

    /**
     * Suggest articles based on ticket title keywords.
     */
    public function suggestArticles(int $buId, string $title): Collection
    {
        // Extract meaningful keywords (skip short/common words)
        $words = array_filter(
            explode(' ', Str::lower($title)),
            fn (string $word): bool => Str::length($word) >= 3
        );

        if (empty($words)) {
            return collect();
        }

        $query = KnowledgeArticle::where('business_unit_id', $buId)
            ->where('is_published', true);

        $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $q->orWhere('title', 'like', '%'.$word.'%')
                    ->orWhere('content', 'like', '%'.$word.'%');
            }
        });

        return $query
            ->with('category')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get();
    }

    /**
     * Track a unique view for an article.
     *
     * Uses a fingerprint composed of IP + user agent to prevent
     * duplicate view counts within a 24-hour window.
     */
    public function trackView(KnowledgeArticle $article, Request $request, ?User $user = null): void
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $fingerprint = md5($ip.$userAgent);

        // Check if this visitor already viewed within the last 24 hours
        $recentView = ArticleView::where('article_id', $article->id)
            ->where('visitor_fingerprint', $fingerprint)
            ->where('viewed_at', '>=', now()->subDay())
            ->exists();

        if ($recentView) {
            return;
        }

        ArticleView::create([
            'article_id' => $article->id,
            'ip_address' => $ip,
            'user_agent' => Str::limit($userAgent, 255),
            'visitor_fingerprint' => $fingerprint,
            'session_id' => $request->session()->getId(),
            'user_id' => $user?->id,
            'viewed_at' => now(),
        ]);

        // Increment the denormalized counter
        $article->increment('views_count');
    }

    /**
     * Link a knowledge article to a ticket.
     *
     * @throws Exception
     */
    public function linkArticleToTicket(Ticket $ticket, int $articleId): void
    {
        $article = KnowledgeArticle::findOrFail($articleId);

        // Prevent duplicate links
        if ($ticket->knowledgeArticles()->where('knowledge_article_id', $articleId)->exists()) {
            return;
        }

        $ticket->knowledgeArticles()->attach($articleId);
    }

    /**
     * Get hierarchical category tree with article counts.
     */
    public function getCategoryTree(int $buId): Collection
    {
        $categories = KnowledgeCategory::where('business_unit_id', $buId)
            ->withCount(['articles', 'publishedArticles'])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // Build tree from flat list
        return $this->buildTree($categories);
    }

    /**
     * Build a hierarchical tree from a flat collection of categories.
     */
    protected function buildTree(Collection $categories, ?int $parentId = null): Collection
    {
        return $categories
            ->where('parent_id', $parentId)
            ->map(function (KnowledgeCategory $category) use ($categories) {
                $category->setRelation('children', $this->buildTree($categories, $category->id));

                return $category;
            })
            ->values();
    }
}
