<?php

namespace App\Models\Modules\Purchasing\PurchaseRequest\Concerns;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberSequence;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PrCategory;
use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasPurchaseRequestRelations
{
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PrCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(NumberSequence::class, 'sequence_id');
    }

    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    public function offlineApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offline_approved_by');
    }

    public function isOfflineApproved(): bool
    {
        return $this->offline_approved_at !== null;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrItem::class)->orderBy('item_order');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PrApproval::class)->orderBy('step_order');
    }

    public function pendingApprovals(): HasMany
    {
        return $this->approvals()->where('status', 'pending');
    }

    public function adminTask(): MorphOne
    {
        return $this->morphOne(AdminTask::class, 'taskable');
    }

    public function currentApproval()
    {
        return $this->approvals()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }

    public function getApprovalProgress(): array
    {
        $total = $this->approvals()->count();
        $approved = $this->approvals()->where('status', 'approved')->count();

        return [
            'approved' => $approved,
            'total' => $total,
        ];
    }

    public function getApprovalProgressText(): string
    {
        $progress = $this->getApprovalProgress();

        return "{$progress['approved']}/{$progress['total']}";
    }
}
