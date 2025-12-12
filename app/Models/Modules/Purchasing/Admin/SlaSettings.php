<?php

namespace App\Models\Modules\Purchasing\Admin;

use App\Models\Core\BusinessUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $business_unit_id
 * @property int $followup_sla_hours
 * @property int $completion_sla_hours
 * @property bool $email_alerts_enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read BusinessUnit|null $businessUnit
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings global()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings whereCompletionSlaHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings whereEmailAlertsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings whereFollowupSlaHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SlaSettings whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SlaSettings extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_unit_id',
        'followup_sla_hours',
        'completion_sla_hours',
        'email_alerts_enabled',
    ];

    protected $casts = [
        'followup_sla_hours' => 'integer',
        'completion_sla_hours' => 'integer',
        'email_alerts_enabled' => 'boolean',
    ];

    /**
     * Get the business unit (null for global settings)
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Scope for global SLA settings
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('business_unit_id');
    }

    /**
     * Scope for business unit specific SLA settings
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
