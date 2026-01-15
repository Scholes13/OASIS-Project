<?php

namespace App\Models\Modules\Activity;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskParticipant extends Pivot
{
    protected $table = 'task_participants';

    public $incrementing = true;

    protected $fillable = [
        'employee_task_id',
        'user_id',
        'is_owner',
        'joined_at',
    ];

    protected $casts = [
        'is_owner' => 'boolean',
        'joined_at' => 'datetime',
    ];

    /**
     * Get the employee task
     */
    public function employeeTask(): BelongsTo
    {
        return $this->belongsTo(EmployeeTask::class, 'employee_task_id');
    }

    /**
     * Get the user (participant)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get only owners
     */
    public function scopeOwners($query)
    {
        return $query->where('is_owner', true);
    }

    /**
     * Scope: Get only joiners (non-owners)
     */
    public function scopeJoiners($query)
    {
        return $query->where('is_owner', false);
    }
}
