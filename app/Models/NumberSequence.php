<?php

namespace App\Models;

use App\Models\Modules\WNS\PurchaseRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $business_unit_id
 * @property int $numbering_module_id
 * @property int|null $department_id
 * @property int $year
 * @property int $month
 * @property int $current_number
 * @property int $max_number
 * @property array<array-key, mixed>|null $void_numbers
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\BusinessUnit $businessUnit
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\NumberingModule $numberingModule
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseRequest> $purchaseRequests
 * @property-read int|null $purchase_requests_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence forPeriod($businessUnitId, $moduleId, $departmentId, $year, $month)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereCurrentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereMaxNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereNumberingModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereVoidNumbers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NumberSequence whereYear($value)
 *
 * @mixin \Eloquent
 */
class NumberSequence extends Model
{
    use LogsActivity;

    protected $fillable = [
        'business_unit_id',
        'numbering_module_id',
        'department_id',
        'year',
        'month',
        'current_number',
        'max_number',
        'void_numbers',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'current_number' => 'integer',
        'max_number' => 'integer',
        'void_numbers' => 'array',
    ];

    /**
     * Get the business unit
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the numbering module
     */
    public function numberingModule(): BelongsTo
    {
        return $this->belongsTo(NumberingModule::class);
    }

    /**
     * Get the department (nullable for cross-department sequences)
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class)->withDefault();
    }

    /**
     * Get purchase requests using this sequence
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class, 'sequence_id');
    }

    /**
     * Get the next number in sequence (with row locking for concurrency)
     */
    public function getNextNumber(): int
    {
        return DB::transaction(function () {
            // Lock the row for update to prevent race conditions
            $sequence = self::where('id', $this->id)->lockForUpdate()->first();

            $nextNumber = $sequence->current_number + 1;

            // Check if we've exceeded the maximum
            if ($nextNumber > $sequence->max_number) {
                throw new \Exception('Maximum number reached for this sequence');
            }

            // Update the current number
            $sequence->update(['current_number' => $nextNumber]);

            return $nextNumber;
        });
    }

    /**
     * Add a number to void list for resequencing
     */
    public function addVoidNumber(int $number): void
    {
        $voidNumbers = $this->void_numbers ?? [];

        if (! in_array($number, $voidNumbers)) {
            $voidNumbers[] = $number;
            sort($voidNumbers);
            $this->update(['void_numbers' => $voidNumbers]);
        }
    }

    /**
     * Remove a number from void list
     */
    public function removeVoidNumber(int $number): void
    {
        $voidNumbers = $this->void_numbers ?? [];

        if (($key = array_search($number, $voidNumbers)) !== false) {
            unset($voidNumbers[$key]);
            $this->update(['void_numbers' => array_values($voidNumbers)]);
        }
    }

    /**
     * Get the next available number (considering voids for resequencing)
     */
    public function getNextAvailableNumber(): int
    {
        $voidNumbers = $this->void_numbers ?? [];

        // If there are void numbers, return the smallest one
        if (! empty($voidNumbers)) {
            $number = min($voidNumbers);
            $this->removeVoidNumber($number);

            return $number;
        }

        // Otherwise, get the next sequential number
        return $this->getNextNumber();
    }

    /**
     * Resequence all numbers after a void
     */
    public function resequenceAfterVoid(int $voidedNumber): void
    {
        // This would be implemented in a job for performance
        // Add the voided number to the void list
        $this->addVoidNumber($voidedNumber);
    }

    /**
     * Get formatted number with padding
     */
    public function getFormattedNumber(int $number, int $padding = 3): string
    {
        return str_pad($number, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for specific business unit, module, department, year, month
     */
    public function scopeForPeriod($query, $businessUnitId, $moduleId, $departmentId, $year, $month)
    {
        return $query->where('business_unit_id', $businessUnitId)
            ->where('numbering_module_id', $moduleId)
            ->where('department_id', $departmentId)
            ->where('year', $year)
            ->where('month', $month);
    }

    /**
     * Create or get sequence for a specific period (supports nullable department_id for cross-department)
     */
    public static function getOrCreateSequence(
        int $businessUnitId,
        int $moduleId,
        ?int $departmentId,
        int $year,
        int $month,
        int $maxNumber = 999
    ): self {
        return self::firstOrCreate(
            [
                'business_unit_id' => $businessUnitId,
                'numbering_module_id' => $moduleId,
                'department_id' => $departmentId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'current_number' => 0,
                'max_number' => $maxNumber,
                'void_numbers' => [],
            ]
        );
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
