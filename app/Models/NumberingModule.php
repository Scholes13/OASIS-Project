<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $business_unit_id
 * @property string $module_code
 * @property string $module_name
 * @property string $format_pattern
 * @property array<array-key, mixed>|null $config
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\BusinessUnit $businessUnit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NumberSequence> $numberSequences
 * @property-read int|null $number_sequences_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule byCode($moduleCode)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereFormatPattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereModuleCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereModuleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberingModule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NumberingModule extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_unit_id',
        'module_code',
        'module_name',
        'format_pattern',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business unit that owns this numbering module
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get number sequences for this module
     */
    public function numberSequences(): HasMany
    {
        return $this->hasMany(NumberSequence::class);
    }

    /**
     * Scope for active modules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for modules in a specific business unit
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope for specific module code
     */
    public function scopeByCode($query, $moduleCode)
    {
        return $query->where('module_code', $moduleCode);
    }

    /**
     * Get the configuration value for a specific key
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set a configuration value
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
    }

    /**
     * Parse the format pattern with given variables
     */
    public function parseFormatPattern(array $variables): string
    {
        $pattern = $this->format_pattern;
        
        foreach ($variables as $key => $value) {
            $pattern = str_replace('{' . $key . '}', $value, $pattern);
        }
        
        return $pattern;
    }

    /**
     * Get current sequence for a department, year, and month (supports cross-department)
     */
    public function getCurrentSequence(?int $departmentId, int $year, int $month): ?NumberSequence
    {
        return $this->numberSequences()
            ->where('department_id', $departmentId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
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
