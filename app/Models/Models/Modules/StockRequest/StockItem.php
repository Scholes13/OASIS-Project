<?php

namespace App\Models\Modules\StockRequest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockItem extends Model
{
    use LogsActivity;

    protected $table = 'stock_items';

    protected $fillable = [
        'stock_request_id',
        'item_order',
        'item_name',
        'quantity',
        'unit',
        'specifications',
        'item_code',
        'image_path',
        'notes',
    ];

    protected $casts = [
        'item_order' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * Get the stock request
     */
    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
    }

    /**
     * Scope for specific stock request
     */
    public function scopeForStockRequest($query, $stockRequestId)
    {
        return $query->where('stock_request_id', $stockRequestId);
    }

    /**
     * Scope ordered by item_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('item_order');
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'item_name',
                'quantity',
                'unit',
                'item_code',
                'specifications',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
