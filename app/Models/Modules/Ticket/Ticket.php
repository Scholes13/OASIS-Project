<?php

namespace App\Models\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'business_unit_id',
        'ticket_number',
        'title',
        'description',
        'requester_id',
        'department_id',
        'status',
        'priority',
        'category_id',
        'assigned_to',
        'created_by',
        'follow_up_at',
        'resolved_at',
        'form_token',
    ];

    protected $casts = [
        'follow_up_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business unit this ticket belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the department this ticket belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the category of this ticket.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Get the user assigned to this ticket.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the requester of this ticket.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the user who created this ticket.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the comments on this ticket.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * Get the attachments on this ticket.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get the knowledge articles linked to this ticket.
     */
    public function knowledgeArticles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'ticket_knowledge_article');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Filter tickets with status "waiting".
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope: Filter tickets with status "in_progress".
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Filter tickets with status "done".
     */
    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', 'done');
    }

    /**
     * Scope: Filter tickets with status "cancelled".
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: Filter by a single business unit.
     */
    public function scopeForBusinessUnit(Builder $query, int $buId): Builder
    {
        return $query->where('business_unit_id', $buId);
    }

    /**
     * Scope: Filter by multiple business units.
     */
    public function scopeForBusinessUnits(Builder $query, array $buIds): Builder
    {
        return $query->whereIn('business_unit_id', $buIds);
    }

    // ==================== SLA HELPERS ====================

    /**
     * In-memory SLA settings cache used by isSlaBreach() and the
     * sla_deadline accessor.  When a caller preloads a list of tickets
     * through {@see Ticket::preloadSlaSettings()} we can avoid running
     * one TicketSlaSettings query per ticket.
     *
     * Keyed first by business_unit_id, then by priority.
     *
     * @var array<int, array<string, int>>|null
     */
    protected static ?array $preloadedSlaSettings = null;

    /**
     * Preload SLA settings for the given business units so subsequent
     * calls to isSlaBreach()/sla_deadline can resolve resolution hours
     * without hitting the database per ticket.
     *
     * @param  iterable<int>  $buIds
     */
    public static function preloadSlaSettings(iterable $buIds): void
    {
        $ids = array_values(array_unique(array_map('intval', is_array($buIds) ? $buIds : iterator_to_array($buIds))));

        if (empty($ids)) {
            self::$preloadedSlaSettings = [];

            return;
        }

        $rows = TicketSlaSettings::whereIn('business_unit_id', $ids)
            ->get(['business_unit_id', 'priority', 'resolution_hours']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->business_unit_id][$row->priority] = (int) $row->resolution_hours;
        }

        self::$preloadedSlaSettings = $map;
    }

    /**
     * Reset the preloaded SLA settings map.  Useful for tests that mutate
     * SLA settings between assertions.
     */
    public static function clearPreloadedSlaSettings(): void
    {
        self::$preloadedSlaSettings = null;
    }

    /**
     * Resolve resolution hours from the preload cache when available,
     * falling back to a single targeted query so production paths that
     * skip the preload still behave correctly.
     */
    protected function resolveResolutionHours(): ?int
    {
        if (self::$preloadedSlaSettings !== null) {
            return self::$preloadedSlaSettings[(int) $this->business_unit_id][$this->priority] ?? null;
        }

        return TicketSlaSettings::getResolutionHours(
            (int) $this->business_unit_id,
            $this->priority
        );
    }

    /**
     * Check if this ticket has breached its SLA deadline.
     */
    public function isSlaBreach(): bool
    {
        $deadline = $this->sla_deadline;

        if ($deadline === null) {
            return false;
        }

        // If resolved, check against resolved_at; otherwise check against now
        $compareTime = $this->resolved_at ?? now();

        return $compareTime->greaterThan($deadline);
    }

    /**
     * Get the SLA deadline for this ticket based on its priority and business unit.
     */
    public function getSlaDeadlineAttribute(): ?Carbon
    {
        if (! $this->business_unit_id || ! $this->priority || ! $this->created_at) {
            return null;
        }

        $resolutionHours = $this->resolveResolutionHours();

        if ($resolutionHours === null) {
            return null;
        }

        return $this->created_at->copy()->addHours($resolutionHours);
    }

    /**
     * Get the human-readable processing time from creation to resolution.
     */
    public function getProcessingTimeAttribute(): ?string
    {
        if (! $this->created_at || ! $this->resolved_at) {
            return null;
        }

        $diff = $this->created_at->diff($this->resolved_at);

        $parts = [];

        if ($diff->days > 0) {
            $parts[] = $diff->days.'d';
        }

        if ($diff->h > 0) {
            $parts[] = $diff->h.'h';
        }

        if ($diff->i > 0) {
            $parts[] = $diff->i.'m';
        }

        if (empty($parts)) {
            return '< 1m';
        }

        return implode(' ', $parts);
    }
}
