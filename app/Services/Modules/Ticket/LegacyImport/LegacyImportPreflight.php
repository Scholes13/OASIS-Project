<?php

namespace App\Services\Modules\Ticket\LegacyImport;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class LegacyImportPreflight
{
    public function __construct(
        private readonly LegacyMappingResolver $mappings,
        private readonly LegacyAttachmentStorage $attachments,
    ) {}

    public function inspect(ConnectionInterface $legacy, LegacyImportOptions $options): LegacyImportReport
    {
        $report = new LegacyImportReport;
        $this->assertSchemas($legacy);
        $this->mappings->preload($legacy);

        $root = null;
        if ($options->copyAttachments) {
            try {
                $root = $this->attachments->validateRoot($options->legacyStorage);
            } catch (Throwable $exception) {
                $report->error($exception->getMessage());
            }
        }

        $categoryIds = $legacy->table('categories')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $categoryLookup = array_fill_keys($categoryIds, true);
        $ticketIds = [];
        $ticketNumbers = [];
        $query = $legacy->table('tickets')->orderBy('id');
        if ($options->limit !== null) {
            $query->limit($options->limit);
        }

        foreach ($query->cursor() as $ticket) {
            $ticketId = (int) $ticket->id;
            $ticketIds[$ticketId] = true;
            $report->increment('tickets_inspected');
            $this->mappings->requester($ticket, $options, $report);
            $this->mappings->assignee($ticket, $options, $report);
            $this->mappings->department($ticket->department, $options, $report);

            if (! empty($ticket->category_id) && ! isset($categoryLookup[(int) $ticket->category_id])) {
                $report->warning("Ticket source id {$ticketId} references a missing category.");
                $report->increment('missing_categories');
            }

            $number = (string) $ticket->ticket_number;
            if (isset($ticketNumbers[$number])) {
                $report->error("Duplicate legacy ticket number [{$number}].");
            }
            $ticketNumbers[$number] = true;

            $identity = DB::table('tickets')
                ->where('import_source', $options->source)
                ->where('import_id', (string) $ticketId)
                ->first();
            $numberOwner = DB::table('tickets')->where('ticket_number', $number)->first();
            if ($numberOwner !== null && ($identity === null || (int) $numberOwner->id !== (int) $identity->id)) {
                $report->error("Native or foreign ticket-number collision [{$number}].");
                $report->increment('ticket_number_collisions');
            }
        }

        $this->inspectComments($legacy, $options, $report, $ticketIds, $root);
        $this->inspectStandaloneAttachments($legacy, $options, $report, $ticketIds, $root);

        foreach (['missing_requesters', 'missing_assignees', 'missing_comment_users', 'missing_departments'] as $metric) {
            if (($report->stats()[$metric] ?? 0) > 0) {
                $report->warning("{$metric} will use the configured fallback mapping.");
            }
        }

        return $report;
    }

    /** @param array<int, bool> $ticketIds */
    private function inspectComments(
        ConnectionInterface $legacy,
        LegacyImportOptions $options,
        LegacyImportReport $report,
        array $ticketIds,
        ?string $root,
    ): void {
        foreach ($legacy->table('comments')->orderBy('id')->cursor() as $comment) {
            if (! isset($ticketIds[(int) $comment->ticket_id])) {
                if ($options->limit === null) {
                    $report->error("Orphan legacy comment source id {$comment->id}.");
                }

                continue;
            }

            $report->increment('comments_inspected');
            $this->mappings->commentUserId(isset($comment->user_id) ? (int) $comment->user_id : null, $options, $report);
            $path = isset($comment->attachment_path) ? trim((string) $comment->attachment_path) : '';
            if ($path !== '') {
                $report->increment('comment_attachments_inspected');
                $this->inspectFile($root, $path, $options, $report);
            }
        }
    }

    /** @param array<int, bool> $ticketIds */
    private function inspectStandaloneAttachments(
        ConnectionInterface $legacy,
        LegacyImportOptions $options,
        LegacyImportReport $report,
        array $ticketIds,
        ?string $root,
    ): void {
        foreach ($legacy->table('attachments')->orderBy('id')->cursor() as $attachment) {
            if (! isset($ticketIds[(int) $attachment->ticket_id])) {
                if ($options->limit === null) {
                    $report->error("Orphan legacy attachment source id {$attachment->id}.");
                }

                continue;
            }

            $report->increment('standalone_attachments_inspected');
            $this->inspectFile($root, (string) $attachment->file_path, $options, $report);
        }
    }

    private function inspectFile(
        ?string $root,
        string $path,
        LegacyImportOptions $options,
        LegacyImportReport $report,
    ): void {
        if (! $options->copyAttachments || $root === null) {
            return;
        }

        try {
            $this->attachments->resolveSource($root, $path);
        } catch (LegacyAttachmentPathException $exception) {
            if ($exception->missing) {
                $report->warning($exception->getMessage().' It will be skipped.');
                $report->increment('missing_files');

                return;
            }

            $report->error($exception->getMessage());
            $report->increment('unsafe_files');
        }
    }

    private function assertSchemas(ConnectionInterface $legacy): void
    {
        foreach (['categories', 'tickets', 'comments', 'attachments', 'staff', 'users'] as $table) {
            if (! $legacy->getSchemaBuilder()->hasTable($table)) {
                throw new \RuntimeException("Legacy table [{$table}] was not found.");
            }
        }

        foreach (['tickets', 'ticket_comments', 'ticket_attachments'] as $table) {
            if (! Schema::hasColumns($table, ['import_source', 'import_id'])) {
                throw new \RuntimeException("Run the import identity migration before importing [{$table}].");
            }
        }
    }
}
