<?php

namespace App\Models\Modules\Purchasing\PurchaseRequest;

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
 * @property-read \App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest $purchaseRequest
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
        'image_path',
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
            // ✅ FIX: Add null safety check to prevent crash during cascade delete
            if ($item->purchaseRequest) {
                $item->purchaseRequest->updateTotalAmount();
            }
            
            // Auto-delete image when item is deleted
            $item->deleteImage();
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
     * Get full URL for item image
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        
        return \Storage::url($this->image_path);
    }

    /**
     * Check if item has image
     */
    public function hasImage(): bool
    {
        return !empty($this->image_path) && \Storage::exists($this->image_path);
    }

    /**
     * Delete item image from storage
     */
    public function deleteImage(): bool
    {
        if ($this->image_path && \Storage::exists($this->image_path)) {
            return \Storage::delete($this->image_path);
        }
        
        return false;
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
