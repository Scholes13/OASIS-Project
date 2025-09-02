<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BusinessUnit extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'description',
        'address',
        'phone',
        'email',
        'parent_id',
        'manager_id',
        'numbering_config',
        'is_active',
    ];

    protected $casts = [
        'numbering_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get departments for this business unit
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get active departments for this business unit
     */
    public function activeDepartments(): HasMany
    {
        return $this->departments()->where('is_active', true);
    }

    /**
     * Get numbering modules for this business unit
     */
    public function numberingModules(): HasMany
    {
        return $this->hasMany(NumberingModule::class);
    }

    /**
     * Get active numbering modules for this business unit
     */
    public function activeNumberingModules(): HasMany
    {
        return $this->numberingModules()->where('is_active', true);
    }

    /**
     * Get user business unit assignments
     */
    public function userBusinessUnits(): HasMany
    {
        return $this->hasMany(UserBusinessUnit::class);
    }

    /**
     * Get users assigned to this business unit
     */
    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            UserBusinessUnit::class,
            'business_unit_id', // Foreign key on UserBusinessUnit table
            'id', // Foreign key on User table
            'id', // Local key on BusinessUnit table
            'user_id' // Local key on UserBusinessUnit table
        );
    }

    /**
     * Get purchase requests for this business unit
     */
    public function purchaseRequests()
    {
        return $this->hasMany(\App\Models\Modules\WNS\PurchaseRequest::class, 'business_unit_id');
    }

    /**
     * Get parent business unit
     */
    public function parent()
    {
        return $this->belongsTo(BusinessUnit::class, 'parent_id');
    }

    /**
     * Get child business units
     */
    public function children(): HasMany
    {
        return $this->hasMany(BusinessUnit::class, 'parent_id');
    }

    /**
     * Get manager of this business unit
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get all descendant business units (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Check if this business unit is a parent of given business unit
     */
    public function isParentOf(BusinessUnit $businessUnit): bool
    {
        return $this->children()->where('id', $businessUnit->id)->exists() ||
               $this->children()->get()->contains(function ($child) use ($businessUnit) {
                   return $child->isParentOf($businessUnit);
               });
    }

    /**
     * Get all business units that this user can access (including children)
     */
    public function getAccessibleBusinessUnits(): array
    {
        $accessible = [$this->id];
        
        foreach ($this->descendants as $descendant) {
            $accessible[] = $descendant->id;
            $accessible = array_merge($accessible, $descendant->getAccessibleBusinessUnits());
        }
        
        return array_unique($accessible);
    }

    /**
     * Get number sequences for this business unit
     */
    public function numberSequences(): HasMany
    {
        return $this->hasMany(NumberSequence::class);
    }

    /**
     * Scope for active business units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
