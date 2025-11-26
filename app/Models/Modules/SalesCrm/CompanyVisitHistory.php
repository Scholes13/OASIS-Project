<?php

namespace App\Models\Modules\SalesCrm;

use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyVisitHistory extends Model
{
    use HasFactory;

    protected $table = 'company_visit_history';

    protected $fillable = [
        'business_unit_id',
        'company_name',
        'department',
        'activity_id',
        'contact_id',
        'user_id',
        'last_visit_at',
        'total_visits',
    ];

    protected $casts = [
        'last_visit_at' => 'datetime',
    ];

    /**
     * Get the business unit
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Get the last activity
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the last contact person
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the last sales person who visited
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get history for current business unit
     */
    public function scopeForBusinessUnit($query, int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Scope: Get recently visited companies
     */
    public function scopeRecentlyVisited($query, int $days = 30)
    {
        return $query->where('last_visit_at', '>=', now()->subDays($days))
            ->orderBy('last_visit_at', 'desc');
    }

    /**
     * Scope: Get most visited companies
     */
    public function scopeMostVisited($query, int $limit = 10)
    {
        return $query->orderBy('total_visits', 'desc')->limit($limit);
    }

    /**
     * Get formatted last visit date
     */
    public function getLastVisitFormatted(): string
    {
        return $this->last_visit_at?->format('d M Y') ?? 'Never visited';
    }

    /**
     * Get days since last visit
     */
    public function getDaysSinceLastVisit(): ?int
    {
        return $this->last_visit_at?->diffInDays(now());
    }

    /**
     * Check if visit is recent (within 7 days)
     */
    public function isRecentVisit(): bool
    {
        $daysSinceLastVisit = $this->getDaysSinceLastVisit();

        return $daysSinceLastVisit !== null && $daysSinceLastVisit <= 7;
    }

    /**
     * Get company full name (with department if available)
     */
    public function getCompanyFullName(): string
    {
        return $this->department
            ? "{$this->company_name} - {$this->department}"
            : $this->company_name;
    }
}
