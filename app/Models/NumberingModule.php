<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
