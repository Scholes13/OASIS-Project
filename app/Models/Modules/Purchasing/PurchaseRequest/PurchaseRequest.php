<?php

namespace App\Models\Modules\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\Concerns\HasPurchaseRequestRelations;
use App\Models\Modules\Purchasing\PurchaseRequest\Concerns\HasPurchaseRequestWorkflow;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $pr_number
 * @property int $business_unit_id
 * @property int $department_id
 * @property int $user_id
 * @property int $sequence_id
 * @property string $used_for
 * @property \Illuminate\Support\Carbon $date_of_request
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property array<array-key, mixed>|null $approval_workflow
 * @property bool $is_sequential_approval
 * @property numeric $total_amount
 * @property string $currency
 * @property array<array-key, mixed>|null $edit_history
 * @property int|null $last_modified_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $keperluan
 * @property \Illuminate\Support\Carbon|null $expected_date
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PrApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read BusinessUnit $businessUnit
 * @property-read Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PrItem> $items
 * @property-read int|null $items_count
 * @property-read User|null $lastModifiedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modules\Purchasing\PurchaseRequest\PrApproval> $pendingApprovals
 * @property-read int|null $pending_approvals_count
 * @property-read NumberSequence $sequence
 * @property-read User $user
 *
 * @mixin \Eloquent
 */
class PurchaseRequest extends Model
{
    use HasPurchaseRequestRelations, HasPurchaseRequestWorkflow, LogsActivity;

    protected $table = 'purchase_requests';

    protected $fillable = [
        'pr_number',
        'business_unit_id',
        'department_id',
        'category_id',
        'user_id',
        'sequence_id',
        'used_for',
        'date_of_request', // Auto dari PR number creation
        'expected_date', // User input - kapan barang dibutuhkan
        'designated_date', // Saved from expected_date field
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'voided_at',
        'offline_approved_at',
        'offline_approved_by',
        'offline_approval_notes',
        'offline_approval_document_path',
        'offline_approval_document_name',
        'approval_workflow',
        'is_sequential_approval',
        'total_amount',
        'currency',
        'supporting_document_path',
        'supporting_document_name',
        'edit_history',
        'last_modified_by',
    ];

    protected $casts = [
        'date_of_request' => 'date',
        'expected_date' => 'date',
        'designated_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'voided_at' => 'datetime',
        'offline_approved_at' => 'datetime',
        'approval_workflow' => 'array',
        'is_sequential_approval' => 'boolean',
        'total_amount' => 'decimal:2',
        'edit_history' => 'array',
    ];

    /**
     * Scope for specific status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for draft PRs
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for submitted PRs
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for approved PRs
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected PRs
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for voided PRs
     */
    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    /**
     * Scope for PRs by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for PRs by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for PRs in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_of_request', [$startDate, $endDate]);
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
