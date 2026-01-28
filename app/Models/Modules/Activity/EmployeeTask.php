<?php

namespace App\Models\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeTask extends Model
{
    use LogsActivity;

    protected $table = 'employee_tasks';

    protected $fillable = [
        'business_unit_id',
        'department_id',
        'created_by',
        'activity_type_id',
        'sub_activity_id',
        'task_title',
        'task_date',
        'due_date',
        'notes',
        'status',
        'priority',
        'started_at',
        'completed_at',
        'completed_by',
        'duration_minutes',
        'source',
        'source_reference_id',
        'validation_status',
        'cancellation_reason',
    ];

    protected $casts = [
        'task_date' => 'date',
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['task_title', 'status', 'due_date', 'started_at', 'completed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business unit this task belongs to
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the department this task belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created this task
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who completed this task
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the activity type
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    /**
     * Get the sub-activity
     */
    public function subActivity(): BelongsTo
    {
        return $this->belongsTo(SubActivity::class, 'sub_activity_id');
    }

    /**
     * Get all participants (many-to-many through pivot)
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_participants', 'employee_task_id', 'user_id')
            ->withPivot(['is_owner', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get task attachments
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'employee_task_id');
    }

    /**
     * Get task validations
     */
    public function validations(): HasMany
    {
        return $this->hasMany(TaskValidation::class, 'employee_task_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Filter by business unit
     */
    public function scopeForBusinessUnit($query, int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope: Filter by department
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope: Filter by participant (user is a participant)
     */
    public function scopeForParticipant($query, int $userId)
    {
        return $query->whereHas('participants', fn ($q) => $q->where('user_id', $userId));
    }

    /**
     * Scope: Get overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by activity type
     */
    public function scopeByActivityType($query, int $activityTypeId)
    {
        return $query->where('activity_type_id', $activityTypeId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Check if user is the owner of this task
     */
    public function isOwner(int $userId): bool
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->where('is_owner', true)
            ->exists();
    }

    /**
     * Check if user is a participant of this task
     */
    public function isParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * Check if task can be edited by user
     */
    public function canBeEditedBy(User $user): bool
    {
        // Owner can always edit
        if ($this->isOwner($user->id)) {
            return true;
        }

        // Department head/leader can edit department tasks
        $accessLevel = $user->getAccessLevel();
        if (in_array($accessLevel, ['department_head', 'team_leader']) && $this->department_id === $user->primary_department_id) {
            return true;
        }

        // Super admin can edit all
        return $user->isSuperAdmin();
    }

    /**
     * Check if task can be started
     */
    public function canBeStarted(): bool
    {
        return $this->status === 'planned';
    }

    /**
     * Check if task can be started by user
     */
    public function canBeStartedBy(User $user): bool
    {
        if (!$this->canBeStarted()) {
            return false;
        }

        // Participant can start
        if ($this->isParticipant($user->id)) {
            return true;
        }

        // Super admin can start
        return $user->isSuperAdmin();
    }

    /**
     * Check if task can be completed
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['planned', 'in_progress']);
    }

    /**
     * Check if task can be completed by user
     */
    public function canBeCompletedBy(User $user): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        // Participant can complete
        if ($this->isParticipant($user->id)) {
            return true;
        }

        // Super admin can complete
        return $user->isSuperAdmin();
    }

    /**
     * Start the task
     */
    public function start(User $user): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the task
     */
    public function complete(User $user): void
    {
        $startedAt = $this->started_at ?? now();
        $durationMinutes = $startedAt->diffInMinutes(now());

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user->id,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Check if task can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'planned' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'planned' => 'Planned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): string
    {
        if (! $this->duration_minutes) {
            return '-';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Check if a user can backdate to a specific date
     * 
     * @param \Carbon\Carbon|string $date The date to check
     * @param User $user The user to check permission for
     * @return bool
     */
    public static function canBackdateTo($date, User $user): bool
    {
        $backdateService = app(BackdatePermissionService::class);
        $taskDate = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
        
        return $backdateService->canCreateTaskWithDate($user, $taskDate);
    }
}
