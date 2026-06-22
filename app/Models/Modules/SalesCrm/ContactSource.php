<?php

namespace App\Models\Modules\SalesCrm;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated SalesCrm module is archived. Retained on disk for reference
 *             only. See docs/exec_plans.md for archive log.
 */
class ContactSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'source_type',
        'source_activity_id',
        'activity_type',
        'source_user_id',
        'source_notes',
        'source_date',
    ];

    protected $casts = [
        'source_date' => 'date',
    ];

    /**
     * Get the contact this source belongs to
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the source activity (if source_type = 'activity')
     */
    public function sourceActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'source_activity_id');
    }

    /**
     * Get the user who created this contact (if source_type = 'manual')
     */
    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    /**
     * Check if source is from activity
     */
    public function isFromActivity(): bool
    {
        return $this->source_type === 'activity';
    }

    /**
     * Check if source is manual entry
     */
    public function isManualEntry(): bool
    {
        return $this->source_type === 'manual';
    }

    /**
     * Get source type label
     */
    public function getSourceTypeLabel(): string
    {
        return match ($this->source_type) {
            'activity' => 'From Activity',
            'manual' => 'Manual Input',
            'import' => 'Bulk Import',
            'referral' => 'Referral',
            'website' => 'Website Form',
            'event' => 'Event/Exhibition',
            default => ucfirst($this->source_type),
        };
    }

    /**
     * Get formatted source description
     */
    public function getSourceDescription(): string
    {
        $dateFormatted = $this->source_date?->format('d M Y') ?? 'Unknown date';
        $userName = $this->sourceUser?->name ?? 'Unknown user';

        return match ($this->source_type) {
            'activity' => "From {$this->activity_type} activity on {$dateFormatted}",
            'manual' => "Manual input by {$userName} on {$dateFormatted}",
            'import' => "Imported on {$dateFormatted}",
            'referral' => "Referral: {$this->source_notes}",
            'website' => "Website form on {$dateFormatted}",
            'event' => "Event: {$this->source_notes}",
            default => $this->source_notes ?? 'Unknown source',
        };
    }
}
