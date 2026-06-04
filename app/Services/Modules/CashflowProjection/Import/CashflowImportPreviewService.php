<?php

namespace App\Services\Modules\CashflowProjection\Import;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use App\Services\Modules\CashflowProjection\LinkedCycleMerger;
use Illuminate\Http\UploadedFile;

class CashflowImportPreviewService
{
    public function __construct(
        protected CashflowFriendlyImportParser $parser,
        protected CashflowImportClassifier $classifier,
        protected CashflowProjectionScopeService $scopeService,
        protected LinkedCycleMerger $linkedCycleMerger
    ) {}

    /**
     * @return array{summary: array<string, int>, rows: array<int, array<string, mixed>>}
     */
    public function preview(UploadedFile $file, User $user, int $activeBusinessUnitId): array
    {
        $parsedRows = $this->parser->parse($file->getRealPath() ?: $file->path());
        $allowedDepartmentIds = $this->scopeService->allowedDepartments($user, $activeBusinessUnitId)->pluck('id')->all();
        $rows = [];

        foreach ($parsedRows as $parsedRow) {
            $rows[] = $this->previewRow($parsedRow, $user, $allowedDepartmentIds);
        }

        return [
            'summary' => $this->buildSummary($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, int>  $allowedDepartmentIds
     * @return array<string, mixed>
     */
    private function previewRow(array $row, User $user, array $allowedDepartmentIds): array
    {
        $classification = $this->classifier->classify($row);
        $errors = $classification['errors'];
        $department = $this->resolveDepartment((string) ($row['business_unit_code'] ?? ''), $classification['department_code']);

        if ($department && ! in_array($department->id, $allowedDepartmentIds, true)) {
            $errors[] = [
                'field' => 'department_code',
                'message' => 'Department tidak berada dalam scope Anda.',
            ];
        }

        if ($department && $department->activeChildren()->exists()) {
            $errors[] = [
                'field' => 'department_code',
                'message' => 'Cashflow line item harus dibuat di sub-department, bukan root department dengan sub-department aktif.',
            ];
        }

        $status = $errors === [] ? 'new' : 'need_review';
        $matchedItem = null;
        $changes = [];

        if ($errors === [] && $department) {
            $cycle = $this->linkedCycleMerger->findOrCreateCycle((int) $department->business_unit_id, (int) substr((string) $row['transaction_date'], 0, 4), $user->id);
            $matchResult = $this->matchExistingLineItem($cycle->id, $department->id, (string) $row['description']);

            if ($matchResult['ambiguous']) {
                $errors[] = [
                    'field' => 'description',
                    'message' => 'Lebih dari satu line item existing memiliki deskripsi sama; pilih manual.',
                ];
                $status = 'need_review';
            } elseif ($matchResult['item']) {
                $matchedItem = $matchResult['item'];
                $changes = $this->buildChanges($matchedItem, $row, $classification['action_code']);
                $status = $changes === [] ? 'no_change' : 'update';
            }
        }

        if ($errors !== [] && $department && $department->activeChildren()->exists()) {
            $status = 'invalid';
        }

        return [
            'row_number' => $row['row_number'],
            'status' => $status,
            'business_unit_code' => $row['business_unit_code'],
            'department_code' => $classification['department_code'],
            'action_code' => $classification['action_code'],
            'action_label' => $classification['action_label'],
            'flow_type' => $classification['flow_type'],
            'transaction_date' => $row['transaction_date'],
            'due_date' => $row['due_date'],
            'amount' => $row['amount'],
            'description' => $row['description'],
            'keterangan' => $row['keterangan'],
            'no_dokumen' => $row['no_dokumen'] ?? null,
            'nama_vendor' => $row['nama_vendor'] ?? null,
            'notes' => $row['notes'],
            'match' => $matchedItem ? ['line_item_id' => $matchedItem->id] : null,
            'changes' => $changes,
            'errors' => $errors,
        ];
    }

    private function resolveDepartment(string $businessUnitCode, ?string $departmentCode): ?Department
    {
        if (! $departmentCode) {
            return null;
        }

        return Department::query()
            ->whereHas('businessUnit', fn ($query) => $query->where('code', strtoupper($businessUnitCode)))
            ->where('code', $departmentCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return array{item: CashflowProjectionLineItem|null, ambiguous: bool}
     */
    private function matchExistingLineItem(int $cycleId, int $departmentId, string $description): array
    {
        $normalizedDescription = $this->normalizeDescription($description);

        $matches = CashflowProjectionLineItem::query()
            ->where('cycle_id', $cycleId)
            ->where('department_id', $departmentId)
            ->get()
            ->filter(fn (CashflowProjectionLineItem $item): bool => $this->normalizeDescription($item->description) === $normalizedDescription)
            ->values();

        return [
            'item' => $matches->count() === 1 ? $matches->first() : null,
            'ambiguous' => $matches->count() > 1,
        ];
    }

    /**
     * @return array<int, array{field: string, old: mixed, new: mixed}>
     */
    private function buildChanges(CashflowProjectionLineItem $item, array $row, ?string $actionCode): array
    {
        $fields = [
            'action_code' => [$item->action_code, $actionCode],
            'transaction_date' => [$item->transaction_date?->format('Y-m-d'), $row['transaction_date']],
            'due_date' => [$item->due_date?->format('Y-m-d'), $row['due_date']],
            'amount' => [(float) $item->amount, (float) $row['amount']],
            'description' => [$item->description, $row['description']],
            'keterangan' => [$item->keterangan, $row['keterangan']],
            'notes' => [$item->notes, $row['notes']],
        ];

        $changes = [];
        foreach ($fields as $field => [$old, $new]) {
            if ($old !== $new) {
                $changes[] = ['field' => $field, 'old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }

    private function normalizeDescription(string $description): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($description)) ?? trim($description));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function buildSummary(array $rows): array
    {
        $summary = [
            'total_rows' => count($rows),
            'ready_rows' => 0,
            'new_rows' => 0,
            'update_rows' => 0,
            'no_change_rows' => 0,
            'need_review_rows' => 0,
            'invalid_rows' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) $row['status'];
            if (isset($summary[$status.'_rows'])) {
                $summary[$status.'_rows']++;
            }
            if (in_array($status, ['new', 'update', 'no_change'], true)) {
                $summary['ready_rows']++;
            }
        }

        return $summary;
    }
}
