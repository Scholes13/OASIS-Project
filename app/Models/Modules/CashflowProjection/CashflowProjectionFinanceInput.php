<?php

namespace App\Models\Modules\CashflowProjection;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowProjectionFinanceInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'month',
        'cash_on_hand',
        'receivable_estimate',
        'upcoming_event_revenue_estimate',
        'capital_injection_estimate',
        'other_income',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'cash_on_hand' => 'decimal:2',
        'receivable_estimate' => 'decimal:2',
        'upcoming_event_revenue_estimate' => 'decimal:2',
        'capital_injection_estimate' => 'decimal:2',
        'other_income' => 'decimal:2',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CashflowProjectionCycle::class, 'cycle_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
