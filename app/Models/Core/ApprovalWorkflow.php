<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $business_unit_id
 * @property string $module_type
 * @property array<array-key, mixed> $approval_steps
 * @property bool $is_sequential
 * @property bool $is_default
 * @property bool $is_active
 * @property array<array-key, mixed>|null $conditions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\BusinessUnit $businessUnit
 * @property-read int $steps_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow default()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow forBusinessUnit($businessUnitId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow forModule($moduleType)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereApprovalSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereBusinessUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereIsSequential($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereModuleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalWorkflow whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApprovalWorkflow extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'business_unit_id',
        'module_type',
        'approval_steps',
        'is_sequential',
        'is_default',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'approval_steps' => 'array',
        'is_sequential' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];

    /**
     * Get the business unit
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Scope for active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default workflows
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for specific business unit
     */
    public function scopeForBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope for specific module type
     */
    public function scopeForModule($query, $moduleType)
    {
        return $query->where('module_type', $moduleType);
    }

    /**
     * Get approval steps count
     */
    public function getStepsCountAttribute(): int
    {
        return count($this->approval_steps ?? []);
    }

    /**
     * Get approver IDs from steps
     */
    public function getApproverIds(): array
    {
        $approverIds = [];

        foreach ($this->approval_steps ?? [] as $step) {
            if (isset($step['approver_id'])) {
                $approverIds[] = $step['approver_id'];
            } elseif (isset($step['approver_ids']) && is_array($step['approver_ids'])) {
                $approverIds = array_merge($approverIds, $step['approver_ids']);
            }
        }

        return array_unique($approverIds);
    }

    /**
     * Add approval step
     */
    public function addStep(array $stepData): void
    {
        $steps = $this->approval_steps ?? [];
        $steps[] = $stepData;
        $this->update(['approval_steps' => $steps]);
    }

    /**
     * Remove approval step by index
     */
    public function removeStep(int $index): void
    {
        $steps = $this->approval_steps ?? [];

        if (isset($steps[$index])) {
            unset($steps[$index]);
            $this->update(['approval_steps' => array_values($steps)]);
        }
    }

    /**
     * Update approval step
     */
    public function updateStep(int $index, array $stepData): void
    {
        $steps = $this->approval_steps ?? [];

        if (isset($steps[$index])) {
            $steps[$index] = array_merge($steps[$index], $stepData);
            $this->update(['approval_steps' => $steps]);
        }
    }

    /**
     * Check if workflow matches conditions
     */
    public function matchesConditions(array $data): bool
    {
        $conditions = $this->conditions ?? [];

        if (empty($conditions)) {
            return true; // No conditions means it matches everything
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (! $field || ! isset($data[$field])) {
                continue;
            }

            $dataValue = $data[$field];

            $matches = match ($operator) {
                '=' => $dataValue == $value,
                '!=' => $dataValue != $value,
                '>' => $dataValue > $value,
                '>=' => $dataValue >= $value,
                '<' => $dataValue < $value,
                '<=' => $dataValue <= $value,
                'in' => in_array($dataValue, (array) $value),
                'not_in' => ! in_array($dataValue, (array) $value),
                'contains' => str_contains((string) $dataValue, (string) $value),
                default => false
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get workflow for specific conditions
     */
    public static function getWorkflowForConditions(int $businessUnitId, string $moduleType, array $data): ?self
    {
        return self::active()
            ->forBusinessUnit($businessUnitId)
            ->forModule($moduleType)
            ->get()
            ->first(function ($workflow) use ($data) {
                return $workflow->matchesConditions($data);
            });
    }

    /**
     * Get default workflow
     */
    public static function getDefaultWorkflow(int $businessUnitId, string $moduleType): ?self
    {
        return self::active()
            ->default()
            ->forBusinessUnit($businessUnitId)
            ->forModule($moduleType)
            ->first();
    }

    /**
     * Clone workflow
     */
    public function cloneWorkflow(string $newName, bool $setAsDefault = false): self
    {
        $cloned = $this->replicate();
        $cloned->name = $newName;
        $cloned->is_default = $setAsDefault;
        $cloned->save();

        if ($setAsDefault) {
            // Remove default flag from other workflows
            self::where('business_unit_id', $this->business_unit_id)
                ->where('module_type', $this->module_type)
                ->where('id', '!=', $cloned->id)
                ->update(['is_default' => false]);
        }

        return $cloned;
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
