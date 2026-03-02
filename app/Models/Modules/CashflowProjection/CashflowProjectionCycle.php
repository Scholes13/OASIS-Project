<?php

namespace App\Models\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashflowProjectionCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_unit_id',
        'year',
        'status',
        'published_at',
        'published_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'published_at' => 'datetime',
    ];

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(CashflowProjectionLineItem::class, 'cycle_id');
    }

    public function financeInputs(): HasMany
    {
        return $this->hasMany(CashflowProjectionFinanceInput::class, 'cycle_id');
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
