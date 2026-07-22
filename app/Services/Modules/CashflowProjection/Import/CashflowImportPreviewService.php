<?php

namespace App\Services\Modules\CashflowProjection\Import;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Services\Modules\CashflowProjection\CashflowMoney;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use Illuminate\Http\UploadedFile;

class CashflowImportPreviewService
{
    public function __construct(
        protected CashflowFriendlyImportParser $parser,
        protected CashflowImportClassifier $classifier,
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowImportTokenService $tokenService
    ) {}

    /**
     * @return array{summary: array<string, int>, rows: array<int, array<string, mixed>>}
     */
    public function preview(UploadedFile $file, User $user, int $activeBusinessUnitId, int $contextYear, int $contextMonth): array
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
            'preview_token' => $this->tokenService->issue($rows, $user, $activeBusinessUnitId, $contextYear, $contextMonth),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $signedRows
     * @param  array<int, array<string, mixed>>  $candidateRows
     * @return array{summary: array<string, int>, rows: array<int, array<string, mixed>>, preview_token: string}
     */
    public function review(
        array $signedRows,
        array $candidateRows,
        string $previewToken,
        User $user,
        int $activeBusinessUnitId,
        int $contextYear,
        int $contextMonth,
    ): array {
        $claimKey = $this->tokenService->claim(
            $previewToken,
            $signedRows,
            $user,
            $activeBusinessUnitId,
            $contextYear,
            $contextMonth,
        );

        try {
            $signedNumbers = collect($signedRows)->pluck('row_number')->sort()->values()->all();
            $candidateNumbers = collect($candidateRows)->pluck('row_number')->sort()->values()->all();
            abort_unless($signedNumbers === $candidateNumbers, 422, 'Row import berubah. Upload ulang file.');

            $allowedDepartmentIds = $this->scopeService
                ->allowedDepartments($user, $activeBusinessUnitId)
                ->pluck('id')
                ->all();
            $rows = array_map(
                fn (array $row): array => $this->previewRow($row, $user, $allowedDepartmentIds),
                $candidateRows,
            );

            return [
                'summary' => $this->buildSummary($rows),
                'rows' => $rows,
                'preview_token' => $this->tokenService->issue(
                    $rows,
                    $user,
                    $activeBusinessUnitId,
                    $contextYear,
                    $contextMonth,
                ),
            ];
        } catch (\Throwable $exception) {
            $this->tokenService->release($claimKey);

            throw $exception;
        }
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

        foreach ([
            'transaction_date' => 'Tanggal pembayaran wajib diisi.',
            'description' => 'Deskripsi wajib diisi.',
            'amount' => 'Nominal wajib diisi.',
        ] as $field => $message) {
            if ($row[$field] === null || $row[$field] === '') {
                $errors[] = ['field' => $field, 'message' => $message];
            }
        }

        if ($row['amount'] !== null && $row['amount'] !== '' && ! is_numeric($row['amount'])) {
            $errors[] = ['field' => 'amount', 'message' => 'Nominal tidak valid.'];
        }
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
            $lineItemId = data_get($row, 'match.line_item_id', $row['line_item_id'] ?? null);
            if ($lineItemId !== null) {
                $matchedItem = CashflowProjectionLineItem::query()->find((int) $lineItemId);
                if (! $matchedItem || ! in_array((int) $matchedItem->department_id, $allowedDepartmentIds, true)) {
                    $errors[] = [
                        'field' => 'line_item_id',
                        'message' => 'Existing Entry ID tidak ditemukan atau tidak berada dalam scope Anda.',
                    ];
                    $status = 'need_review';
                }
            } else {
                $cycleId = CashflowProjectionCycle::query()
                    ->where('business_unit_id', $department->business_unit_id)
                    ->where('year', (int) substr((string) $row['transaction_date'], 0, 4))
                    ->value('id');
                $matchResult = $this->matchExistingLineItem($cycleId ? (int) $cycleId : null, $department->id, (string) $row['description']);

                if ($matchResult['ambiguous']) {
                    $errors[] = [
                        'field' => 'description',
                        'message' => 'Lebih dari satu line item existing memiliki deskripsi sama; pilih manual.',
                    ];
                    $status = 'need_review';
                } elseif ($matchResult['item']) {
                    $matchedItem = $matchResult['item'];
                }
            }

            if ($matchedItem) {
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
            'amount' => is_numeric($row['amount']) ? CashflowMoney::normalize($row['amount']) : $row['amount'],
            'description' => $row['description'],
            'keterangan' => $row['keterangan'],
            'no_dokumen' => $row['no_dokumen'] ?? null,
            'nama_vendor' => $row['nama_vendor'] ?? null,
            'notes' => $row['notes'],
            'is_estimated_date' => $row['is_estimated_date'] ?? false,
            'match' => $matchedItem ? ['line_item_id' => $matchedItem->id] : null,
            'original' => $matchedItem ? $this->lineItemValues($matchedItem) : null,
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
    private function matchExistingLineItem(?int $cycleId, int $departmentId, string $description): array
    {
        if ($cycleId === null) {
            return ['item' => null, 'ambiguous' => false];
        }

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
            'is_estimated_date' => [(bool) $item->is_estimated_date, (bool) ($row['is_estimated_date'] ?? false)],
            'amount' => [CashflowMoney::normalize($item->amount), CashflowMoney::normalize($row['amount'])],
            'description' => [$item->description, $row['description']],
            'keterangan' => [$item->keterangan, $row['keterangan']],
            'no_dokumen' => [$item->no_dokumen, $row['no_dokumen'] ?? null],
            'nama_vendor' => [$item->nama_vendor, $row['nama_vendor'] ?? null],
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

    /** @return array<string, mixed> */
    private function lineItemValues(CashflowProjectionLineItem $item): array
    {
        return [
            'cycle_id' => $item->cycle_id,
            'department_id' => $item->department_id,
            'action_code' => $item->action_code,
            'flow_type' => $item->flow_type,
            'transaction_date' => $item->transaction_date?->format('Y-m-d'),
            'due_date' => $item->due_date?->format('Y-m-d'),
            'is_estimated_date' => (bool) $item->is_estimated_date,
            'amount' => CashflowMoney::normalize($item->amount),
            'description' => $item->description,
            'keterangan' => $item->keterangan,
            'no_dokumen' => $item->no_dokumen,
            'nama_vendor' => $item->nama_vendor,
            'notes' => $item->notes,
        ];
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
