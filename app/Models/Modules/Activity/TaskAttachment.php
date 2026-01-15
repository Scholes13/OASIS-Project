<?php

namespace App\Models\Modules\Activity;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    protected $table = 'task_attachments';

    public $timestamps = false;

    protected $fillable = [
        'employee_task_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
        'created_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Allowed file types for attachments
     */
    public const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Maximum file size in bytes (5MB)
     */
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Maximum attachments per task
     */
    public const MAX_ATTACHMENTS_PER_TASK = 5;

    /**
     * Get the employee task
     */
    public function employeeTask(): BelongsTo
    {
        return $this->belongsTo(EmployeeTask::class, 'employee_task_id');
    }

    /**
     * Get the user who uploaded this attachment
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Get file URL
     */
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get icon class based on file type
     */
    public function getIconClass(): string
    {
        if ($this->isImage()) {
            return 'photo';
        }

        if ($this->isPdf()) {
            return 'document-text';
        }

        return match ($this->file_type) {
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'table-cells',
            default => 'paper-clip',
        };
    }

    /**
     * Validate file type
     */
    public static function isValidType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_TYPES);
    }

    /**
     * Validate file size
     */
    public static function isValidSize(int $size): bool
    {
        return $size <= self::MAX_FILE_SIZE;
    }
}
