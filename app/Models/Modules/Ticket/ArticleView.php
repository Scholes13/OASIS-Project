<?php

namespace App\Models\Modules\Ticket;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleView extends Model
{
    protected $table = 'ticket_article_views';

    protected $fillable = [
        'article_id',
        'ip_address',
        'user_agent',
        'visitor_fingerprint',
        'session_id',
        'user_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the article this view belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'article_id');
    }

    /**
     * Get the user who viewed the article.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
