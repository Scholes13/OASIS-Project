<?php

namespace App\Models\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TicketSlaSettings extends Model
{
    protected $table = 'ticket_sla_settings';

    protected $fillable = [
        'business_unit_id',
        'priority',
        'resolution_hours',
    ];

    protected $casts = [
        'resolution_hours' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business unit this SLA setting belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Get all SLA settings for a business unit.
     */
    public static function getForBusinessUnit(int $buId): Collection
    {
        return static::where('business_unit_id', $buId)->get();
    }

    /**
     * Get the resolution hours for a specific business unit and priority.
     */
    public static function getResolutionHours(int $buId, string $priority): ?int
    {
        return static::where('business_unit_id', $buId)
            ->where('priority', $priority)
            ->value('resolution_hours');
    }
}
