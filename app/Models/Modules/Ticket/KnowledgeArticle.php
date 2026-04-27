<?php

namespace App\Models\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeArticle extends Model
{
    protected $table = 'ticket_knowledge_articles';

    protected $fillable = [
        'business_unit_id',
        'title',
        'slug',
        'content',
        'category_id',
        'is_published',
        'views_count',
        'author_id',
        'published_at',
        'meta_description',
        'tags',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views_count' => 'integer',
        'published_at' => 'datetime',
        'tags' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business unit this article belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the category this article belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }

    /**
     * Get the author of this article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the tickets linked to this article.
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_knowledge_article');
    }

    /**
     * Get the view records for this article.
     */
    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class, 'article_id');
    }
}
