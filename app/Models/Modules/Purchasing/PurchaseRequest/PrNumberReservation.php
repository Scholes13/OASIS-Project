<?php

namespace App\Models\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberSequence;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $pr_number
 * @property int $business_unit_id
 * @property int $department_id
 * @property int $user_id
 * @property int $sequence_id
 * @property string $purpose
 * @property string $description
 * @property string $status
 * @property \Illuminate\Support\Carbon $reserved_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property string|null $void_reason
 * @property int|null $voided_by
 * @property int|null $purchase_request_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PrNumberReservation extends Model
{
    protected $fillable = [
        'pr_number',
        'business_unit_id',
        'department_id',
        'user_id',
        'sequence_id',
        'purpose',
        'description',
        'status',
        'reserved_at',
        'used_at',
        'voided_at',
        'void_reason',
        'voided_by',
        'purchase_request_id',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'used_at' => 'datetime',
        'voided_at' => 'datetime',
    ];


    /**
     * Get the business unit
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who reserved this number
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who voided this number
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Get the number sequence
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(NumberSequence::class, 'sequence_id');
    }

    /**
     * Get the purchase request (if used)
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Scope for reserved numbers
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    /**
     * Scope for used numbers
     */
    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    /**
     * Scope for voided numbers
     */
    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    /**
     * Scope for user's reservations
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if reservation can be voided
     */
    public function canBeVoided(): bool
    {
        return $this->status === 'reserved';
    }

    /**
     * Check if reservation can be used
     */
    public function canBeUsed(): bool
    {
        return $this->status === 'reserved';
    }

    /**
     * Void the reservation
     * ✅ FIX: Made voidedBy required to prevent Auth::id() failures in CLI/queue contexts
     *
     * @param  string  $reason  Reason for voiding
     * @param  int  $voidedBy  User ID who is voiding (required)
     */
    public function void(string $reason, int $voidedBy): bool
    {
        if (! $this->canBeVoided()) {
            return false;
        }

        $this->update([
            'status' => 'voided',
            'voided_at' => now(),
            'void_reason' => $reason,
            'voided_by' => $voidedBy,
        ]);

        return true;
    }

    /**
     * Mark as used
     */
    public function markAsUsed(int $purchaseRequestId): bool
    {
        if (! $this->canBeUsed()) {
            return false;
        }

        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'purchase_request_id' => $purchaseRequestId,
        ]);

        return true;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'reserved' => 'bg-yellow-100 text-yellow-800',
            'used' => 'bg-green-100 text-green-800',
            'voided' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status display text
     */
    public function getStatusDisplayText(): string
    {
        return match ($this->status) {
            'reserved' => 'Reserved',
            'used' => 'Used',
            'voided' => 'Voided',
            default => 'Unknown',
        };
    }

    /**
     * Get days since reserved
     */
    public function getDaysSinceReserved(): int
    {
        return $this->reserved_at->diffInDays(now());
    }
}
