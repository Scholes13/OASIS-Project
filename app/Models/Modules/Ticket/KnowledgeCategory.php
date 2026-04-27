<?php

namespace App\Models\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeCategory extends Model
{
    protected $table = 'ticket_knowledge_categories';

    protected $fillable = [
        'business_unit_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'icon',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business unit this category belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(KnowledgeCategory::class, 'parent_id');
    }

    /**
     * Get all articles in this category.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'category_id');
    }

    /**
     * Get only published articles in this category.
     */
    public function publishedArticles(): HasMany
    {
        return $this->articles()->where('is_published', true);
    }
}
