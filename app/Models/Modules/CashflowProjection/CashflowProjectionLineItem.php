<?php

namespace App\Models\Modules\CashflowProjection;

use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowProjectionLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'department_id',
        'flow_type',
        'action_code',
        'transaction_date',
        'due_date',
        'is_estimated_date',
        'amount',
        'description',
        'keterangan',
        'no_dokumen',
        'nama_vendor',
        'notes',
        'source_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'due_date' => 'date',
        'is_estimated_date' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CashflowProjectionCycle::class, 'cycle_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
