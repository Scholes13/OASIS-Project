<?php

namespace App\Models\Modules\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowProjectionLinkedUnit extends Model
{
    protected $fillable = [
        'host_business_unit_id',
        'linked_business_unit_id',
        'created_by',
    ];

    public function hostBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'host_business_unit_id');
    }

    public function linkedBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class, 'linked_business_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
