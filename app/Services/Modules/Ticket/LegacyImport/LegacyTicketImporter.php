<?php

namespace App\Services\Modules\Ticket\LegacyImport;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class LegacyTicketImporter
{
    private array $categories = [];

    public function __construct(
        private readonly LegacyImportPreflight $preflight,
        private readonly LegacyMappingResolver $mappings,
        private readonly LegacyAttachmentStorage $attachments,
    ) {}

    public function run(
        ConnectionInterface $legacy,
        LegacyImportOptions $options,
        bool $dryRun,
    ): LegacyImportReport {
        $preflight = $this->preflight->inspect($legacy, $options);
        if ($dryRun || $preflight->hasErrors()) {
            return $preflight;
        }

        $report = new LegacyImportReport;
        $report->mergeWarningsFrom($preflight);
        $root = $options->copyAttachments
            ? $this->attachments->validateRoot($options->legacyStorage)
            : null;

        try {
            DB::transaction(function () use ($legacy, $options, $report, $root): void {
                $this->mappings->preload($legacy);
                $this->importCategories($legacy, $options, $report);
                $query = $legacy->table('tickets')->orderBy('id');
                if ($options->limit !== null) {
                    $query->limit($options->limit);
                }

                foreach ($query->cursor() as $legacyTicket) {
                    $ticketId = $this->importTicket($legacyTicket, $options, $report);
                    $this->importComments($legacy, $legacyTicket, $ticketId, $options, $report, $root);
                    $this->importStandaloneAttachments($legacy, $legacyTicket, $ticketId, $options, $report, $root);
                }
            });
        } catch (Throwable $exception) {
            $this->attachments->cleanupNewFiles();
            throw $exception;
        }

        $this->attachments->clearTracking();

        return $report;
    }

    private function importCategories(
        ConnectionInterface $legacy,
        LegacyImportOptions $options,
        LegacyImportReport $report,
    ): void {
        $ticketQuery = $legacy->table('tickets')->orderBy('id');
        if ($options->limit !== null) {
            $ticketQuery->limit($options->limit);
        }
        $categoryIds = $ticketQuery->pluck('category_id')->filter()->unique()->all();
        if ($categoryIds === []) {
            return;
        }

        foreach ($legacy->table('categories')->whereIn('id', $categoryIds)->orderBy('id')->get() as $category) {
            $existing = DB::table('ticket_categories')
                ->where('business_unit_id', $options->businessUnit->id)
                ->where('name', $category->name)
                ->first();

            if ($existing !== null) {
                $this->categories[(int) $category->id] = (int) $existing->id;
                $report->increment('categories_existing');

                continue;
            }

            $this->categories[(int) $category->id] = (int) DB::table('ticket_categories')->insertGetId([
                'business_unit_id' => $options->businessUnit->id,
                'name' => $category->name,
                'description' => $category->description,
                'color' => $category->color ?: '#6366f1',
                'is_active' => true,
                'created_at' => $category->created_at ?? now(),
                'updated_at' => $category->updated_at ?? now(),
            ]);
            $report->increment('categories_created');
        }
    }

    private function importTicket(
        object $legacyTicket,
        LegacyImportOptions $options,
        LegacyImportReport $report,
    ): int {
        $importId = (string) $legacyTicket->id;
        $existing = DB::table('tickets')
            ->where('import_source', $options->source)
            ->where('import_id', $importId)
            ->first();
        $numberOwner = DB::table('tickets')->where('ticket_number', $legacyTicket->ticket_number)->first();
        if ($numberOwner !== null && ($existing === null || (int) $numberOwner->id !== (int) $existing->id)) {
            throw new RuntimeException("Ticket-number collision [{$legacyTicket->ticket_number}].");
        }

        $requester = $this->mappings->requester($legacyTicket, $options, $report);
        $assignee = $options->forceAssignee ?? $this->mappings->assignee($legacyTicket, $options, $report);
        $department = $this->mappings->department($legacyTicket->department, $options, $report);
        $payload = [
            'business_unit_id' => $options->businessUnit->id,
            'ticket_number' => $legacyTicket->ticket_number,
            'title' => $legacyTicket->title,
            'description' => $this->description($legacyTicket),
            'requester_id' => $requester->id,
            'department_id' => $department->id,
            'status' => $this->status($legacyTicket->status),
            'priority' => $this->priority($legacyTicket->priority),
            'category_id' => $this->categories[(int) $legacyTicket->category_id] ?? null,
            'assigned_to' => $assignee?->id,
            'created_by' => $requester->id,
            'follow_up_at' => $legacyTicket->follow_up_at,
            'resolved_at' => $legacyTicket->resolved_at,
            'import_source' => $options->source,
            'import_id' => $importId,
            'updated_at' => $legacyTicket->updated_at ?? now(),
        ];

        if ($existing !== null) {
            if ($options->updateExisting) {
                DB::table('tickets')->where('id', $existing->id)->update($payload);
                $report->increment('tickets_updated');
                $report->incrementWhen('forced_assignee_tickets', $options->forceAssignee !== null);
            } else {
                $report->increment('tickets_existing');
            }

            return (int) $existing->id;
        }

        $payload['created_at'] = $legacyTicket->created_at ?? now();
        $report->increment('tickets_created');
        $report->incrementWhen('forced_assignee_tickets', $options->forceAssignee !== null);

        return (int) DB::table('tickets')->insertGetId($payload);
    }

    private function importComments(
        ConnectionInterface $legacy,
        object $legacyTicket,
        int $ticketId,
        LegacyImportOptions $options,
        LegacyImportReport $report,
        ?string $root,
    ): void {
        foreach ($legacy->table('comments')->where('ticket_id', $legacyTicket->id)->orderBy('id')->get() as $comment) {
            $importId = (string) $comment->id;
            $commentUserId = $this->mappings->commentUserId(isset($comment->user_id) ? (int) $comment->user_id : null, $options, $report);
            $existing = DB::table('ticket_comments')
                ->where('import_source', $options->source)
                ->where('import_id', $importId)
                ->first();
            $payload = [
                'ticket_id' => $ticketId,
                'user_id' => $commentUserId,
                'content' => (string) $comment->content,
                'is_private' => (bool) $comment->is_private,
                'import_source' => $options->source,
                'import_id' => $importId,
                'updated_at' => $comment->updated_at ?? now(),
                'deleted_at' => null,
            ];

            if ($existing === null) {
                $payload['created_at'] = $comment->created_at ?? now();
                $commentId = (int) DB::table('ticket_comments')->insertGetId($payload);
                $report->increment('comments_created');
            } else {
                $commentId = (int) $existing->id;
                if ($options->updateExisting) {
                    DB::table('ticket_comments')->where('id', $commentId)->update($payload);
                    $report->increment('comments_updated');
                } else {
                    $report->increment('comments_existing');
                }
            }

            $path = isset($comment->attachment_path) ? trim((string) $comment->attachment_path) : '';
            if ($path !== '' && $options->copyAttachments && $root !== null) {
                $this->importAttachment($ticketId, $commentId, $commentUserId, $comment, $path, "comment:{$importId}", 'comment', $options, $report, $root);
            }
        }
    }

    private function importStandaloneAttachments(
        ConnectionInterface $legacy,
        object $legacyTicket,
        int $ticketId,
        LegacyImportOptions $options,
        LegacyImportReport $report,
        ?string $root,
    ): void {
        if (! $options->copyAttachments || $root === null) {
            return;
        }

        foreach ($legacy->table('attachments')->where('ticket_id', $legacyTicket->id)->orderBy('id')->get() as $attachment) {
            $this->importAttachment(
                $ticketId,
                null,
                (int) $options->fallbackUser->id,
                $attachment,
                (string) $attachment->file_path,
                'attachment:'.$attachment->id,
                'standalone',
                $options,
                $report,
                $root,
            );
        }
    }

    private function importAttachment(
        int $ticketId,
        ?int $commentId,
        int $uploadedBy,
        object $legacyAttachment,
        string $relativePath,
        string $importId,
        string $kind,
        LegacyImportOptions $options,
        LegacyImportReport $report,
        string $root,
    ): void {
        $existing = DB::table('ticket_attachments')
            ->where('import_source', $options->source)
            ->where('import_id', $importId)
            ->first();
        if ($existing !== null && $this->attachments->existsOnLocalDisk($existing->disk, $existing->file_path)) {
            DB::table('ticket_attachments')->where('id', $existing->id)->update([
                'ticket_id' => $ticketId,
                'comment_id' => $commentId,
                'uploaded_by' => $uploadedBy,
            ]);
            $report->increment('attachments_existing');

            return;
        }

        try {
            $sourcePath = $this->attachments->resolveSource($root, $relativePath);
        } catch (LegacyAttachmentPathException $exception) {
            if (! $exception->missing) {
                throw $exception;
            }

            $report->warning($exception->getMessage().' It was skipped.');
            $report->increment('missing_files');
            $report->increment('attachments_skipped');

            return;
        }

        $originalFilename = $this->originalFilename($legacyAttachment, $relativePath);
        $targetPath = $this->attachments->store(
            $sourcePath,
            $ticketId,
            $kind,
            $importId,
            $originalFilename,
        );
        $fileType = isset($legacyAttachment->file_type) && $legacyAttachment->file_type
            ? (string) $legacyAttachment->file_type
            : (mime_content_type($sourcePath) ?: null);

        $payload = [
            'ticket_id' => $ticketId,
            'comment_id' => $commentId,
            'filename' => basename($targetPath),
            'original_filename' => $originalFilename,
            'file_path' => $targetPath,
            'disk' => 'local',
            'file_type' => $fileType,
            'file_size' => filesize($sourcePath) ?: 0,
            'uploaded_by' => $uploadedBy,
            'import_source' => $options->source,
            'import_id' => $importId,
            'created_at' => $legacyAttachment->created_at ?? now(),
            'updated_at' => $legacyAttachment->updated_at ?? now(),
        ];

        if ($existing !== null) {
            DB::table('ticket_attachments')->where('id', $existing->id)->update($payload);
            $report->increment('attachments_repaired');

            return;
        }

        DB::table('ticket_attachments')->insert($payload);
        $report->increment('attachments_created');
    }

    private function originalFilename(object $legacyAttachment, string $relativePath): string
    {
        if (isset($legacyAttachment->original_filename) && trim((string) $legacyAttachment->original_filename) !== '') {
            return basename(str_replace('\\', '/', (string) $legacyAttachment->original_filename));
        }

        return basename(str_replace('\\', '/', $relativePath));
    }

    private function description(object $ticket): string
    {
        $lines = [
            $ticket->description,
            '',
            '---',
            'Legacy request.werkudara.com import',
            'Legacy requester: '.$ticket->requester_name.' <'.$ticket->requester_email.'>',
        ];

        if (! empty($ticket->requester_phone)) {
            $lines[] = 'Legacy phone: '.$ticket->requester_phone;
        }
        if (! empty($ticket->department)) {
            $lines[] = 'Legacy department: '.$ticket->department;
        }

        return implode(PHP_EOL, array_filter($lines, fn ($line) => $line !== null));
    }

    private function status(?string $status): string
    {
        return in_array($status, ['waiting', 'in_progress', 'done', 'cancelled'], true) ? $status : 'waiting';
    }

    private function priority(?string $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high', 'critical'], true) ? $priority : 'medium';
    }
}
