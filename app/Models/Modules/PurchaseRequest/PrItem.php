<?php

namespace App\Models\Modules\PurchaseRequest;

use App\Models\Core\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $purchase_request_id
 * @property int $item_order
 * @property string $item_name
 * @property string|null $brand_name
 * @property int $expense_department_id
 * @property string|null $item_description
 * @property string|null $supplier_name
 * @property numeric $quantity
 * @property string $unit
 * @property numeric $unit_price
 * @property string $currency
 * @property numeric $total_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Department $expenseDepartment
 * @property-read string $formatted_quantity
 * @property-read string $formatted_total_price
 * @property-read string $formatted_unit_price
 * @property-read \App\Models\Modules\PurchaseRequest\PurchaseRequest $purchaseRequest
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem forPurchaseRequest($purchaseRequestId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereBrandName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereExpenseDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereItemDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereItemOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem wherePurchaseRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereSupplierName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PrItem extends Model
{
    use LogsActivity;

    protected $table = 'pr_items';

    protected $fillable = [
        'purchase_request_id',
        'item_order',
        'item_name',
        'brand_name',
        'expense_department_id',
        'item_description',
        'supplier_name',
        'quantity',
        'unit',
        'unit_price',
        'currency',
        'total_price',
    ];

    protected $casts = [
        'item_order' => 'integer',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the purchase request
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Get the expense department
     */
    public function expenseDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'expense_department_id');
    }

    /**
     * Calculate total price based on quantity and unit price
     */
    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Update total price
     */
    public function updateTotalPrice(): void
    {
        $this->update(['total_price' => $this->calculateTotalPrice()]);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total price before saving
        static::saving(function ($item) {
            $item->total_price = $item->calculateTotalPrice();
        });

        // Update PR total when item is saved or deleted
        static::saved(function ($item) {
            $item->purchaseRequest->updateTotalAmount();
        });

        static::deleted(function ($item) {
            $item->purchaseRequest->updateTotalAmount();
        });
    }

    /**
     * Scope for items in a specific purchase request
     */
    public function scopeForPurchaseRequest($query, $purchaseRequestId)
    {
        return $query->where('purchase_request_id', $purchaseRequestId);
    }

    /**
     * Scope for items ordered by item_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('item_order');
    }

    /**
     * Get formatted quantity with unit
     */
    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->quantity, 2).' '.$this->unit;
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return $this->currency.' '.number_format($this->unit_price, 2);
    }

    /**
     * Get formatted total price
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return $this->currency.' '.number_format($this->total_price, 2);
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
