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

        $resolutionHours = TicketSlaSettings::getResolutionHours(
            $this->business_unit_id,
            $this->priority
        );

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
