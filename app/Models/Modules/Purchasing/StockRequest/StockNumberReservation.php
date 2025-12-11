<?php

namespace App\Models\Modules\Purchasing\StockRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberSequence;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class StockNumberReservation extends Model
{
    protected $fillable = [
        'st_number',
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
        'stock_request_id',
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
     * Get the stock request (if used)
     */
    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
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
     * Mark as used
     */
    public function markAsUsed(int $stockRequestId): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'stock_request_id' => $stockRequestId,
        ]);
    }

    /**
     * Void the reservation
     */
    public function void(string $reason): void
    {
        $this->update([
            'status' => 'voided',
            'voided_at' => now(),
            'void_reason' => $reason,
            'voided_by' => Auth::id(),
        ]);
    }
}
