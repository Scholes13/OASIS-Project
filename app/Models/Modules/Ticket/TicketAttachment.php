<?php

namespace App\Models\Modules\Ticket;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $table = 'ticket_attachments';

    protected $fillable = [
        'ticket_id',
        'comment_id',
        'filename',
        'original_filename',
        'file_path',
        'disk',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Append a signed download URL when the attachment is serialized for
     * the frontend.  The URL points at the authenticated download endpoint
     * so we never expose raw storage paths.
     *
     * @var list<string>
     */
    protected $appends = ['download_url'];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the ticket this attachment belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the comment this attachment belongs to.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'comment_id');
    }

    /**
     * Get the user who uploaded this attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ==================== ACCESSORS ====================

    /**
     * Build the authenticated download URL for this attachment.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('it-support.tickets.attachments.download', [
            'attachment' => $this->getKey(),
        ]);
    }
}
