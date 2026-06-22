<?php

namespace App\Models\Modules\Purchasing\Admin;

use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdminTaskItemRealization extends Model
{
    use LogsActivity;

    protected $table = 'admin_task_item_realizations';

    protected $fillable = [
        'admin_task_id',
        'item_type',
        'item_id',
        'item_name',
        'quantity',
        'unit',
        'estimated_unit_price',
        'estimated_total_price',
        'realized_unit_price',
        'realized_total_price',
        'savings_amount',
        'savings_percentage',
        'original_supplier',
        'realized_supplier',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total_price' => 'decimal:2',
        'realized_unit_price' => 'decimal:2',
        'realized_total_price' => 'decimal:2',
        'savings_amount' => 'decimal:2',
        'savings_percentage' => 'decimal:2',
    ];

    /**
     * Morph map for item types
     */
    public static array $morphMap = [
        'pr_item' => PrItem::class,
        'st_item' => StockItem::class,
    ];

    /**
     * Get the admin task that owns this realization
     */
    public function adminTask(): BelongsTo
    {
        return $this->belongsTo(AdminTask::class);
    }

    /**
     * Get the related item (PrItem or StockItem)
     */
    public function item(): MorphTo
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    /**
     * Resolve the item type to the actual model class
     */
    public function getItemModelAttribute(): ?Model
    {
        $modelClass = self::$morphMap[$this->item_type] ?? null;

        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($this->item_id);
    }

    /**
     * Check if supplier was changed
     */
    public function hasSupplierChanged(): bool
    {
        return $this->original_supplier !== $this->realized_supplier
            && ! empty($this->realized_supplier);
    }

    /**
     * Check if there are positive savings
     */
    public function hasPositiveSavings(): bool
    {
        return $this->savings_amount > 0;
    }

    /**
     * Check if there are negative savings (over budget)
     */
    public function hasNegativeSavings(): bool
    {
        return $this->savings_amount < 0;
    }

    /**
     * Get formatted savings amount
     */
    public function getFormattedSavingsAmountAttribute(): string
    {
        $prefix = $this->savings_amount >= 0 ? '' : '-';

        return $prefix.'Rp '.number_format(abs($this->savings_amount), 2);
    }

    /**
     * Get formatted savings percentage
     */
    public function getFormattedSavingsPercentageAttribute(): string
    {
        $prefix = $this->savings_percentage >= 0 ? '' : '-';

        return $prefix.number_format(abs($this->savings_percentage), 2).'%';
    }

    /**
     * Scope for realizations of a specific admin task
     */
    public function scopeForAdminTask($query, $adminTaskId)
    {
        return $query->where('admin_task_id', $adminTaskId);
    }

    /**
     * Scope for PR item realizations
     */
    public function scopePrItems($query)
    {
        return $query->where('item_type', 'pr_item');
    }

    /**
     * Scope for ST item realizations
     */
    public function scopeStItems($query)
    {
        return $query->where('item_type', 'st_item');
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
