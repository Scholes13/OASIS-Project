<?php

namespace App\Services\Modules\CashflowProjection\Import;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Services\Modules\CashflowProjection\CashflowProjectionTemplateService;

class CashflowImportClassifier
{
    public function __construct(
        protected CashflowProjectionTemplateService $templateService
    ) {}

    /**
     * @param  array<string, mixed>  $row
     * @return array{status: string, department_code: string|null, action_code: string|null, action_label: string|null, flow_type: string|null, errors: array<int, array{field: string, message: string}>}
     */
    public function classify(array $row): array
    {
        $errors = [];
        $department = $this->resolveDepartment($row);

        if (! $department) {
            $errors[] = [
                'field' => 'department_code',
                'message' => 'Department tidak bisa dideteksi dari deskripsi atau no dokumen.',
            ];
        }

        $actionCode = $department ? $this->resolveActionCode($department, (string) ($row['keterangan'] ?? ''), (string) ($row['description'] ?? '')) : null;
        $actionMeta = $actionCode && $department ? $this->templateService->metaForActionCode($actionCode, $department) : null;

        if ($department && (! $actionCode || ! $this->templateService->isActionAllowedForDepartment($actionCode, $department) || ! $actionMeta)) {
            $errors[] = [
                'field' => 'action_code',
                'message' => 'Action tidak bisa dideteksi dari keterangan/deskripsi.',
            ];
            $actionCode = null;
            $actionMeta = null;
        }

        return [
            'status' => $errors === [] ? 'ready' : 'need_review',
            'department_code' => $department?->code,
            'action_code' => $actionCode,
            'action_label' => $actionCode && $department ? $this->templateService->displayLabelForAction($actionCode, $department) : null,
            'flow_type' => $actionMeta['flow_type'] ?? null,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveDepartment(array $row): ?Department
    {
        $businessUnitCode = strtoupper(trim((string) ($row['business_unit_code'] ?? '')));
        $businessUnit = BusinessUnit::query()->where('code', $businessUnitCode)->first();
        if (! $businessUnit) {
            return null;
        }

        $departmentCode = $this->detectDepartmentCode($row);
        if (! $departmentCode) {
            return null;
        }

        return Department::query()
            ->where('business_unit_id', $businessUnit->id)
            ->where('code', $departmentCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function detectDepartmentCode(array $row): ?string
    {
        $explicit = strtoupper(trim((string) ($row['department_code'] ?? '')));
        if ($explicit !== '') {
            return $explicit;
        }

        $businessUnitCode = preg_quote(strtoupper(trim((string) ($row['business_unit_code'] ?? ''))), '/');
        $description = strtoupper((string) ($row['description'] ?? ''));
        if ($businessUnitCode !== '' && preg_match('/\b'.$businessUnitCode.'\s*-\s*([A-Z0-9]+)\s*-/u', $description, $matches)) {
            return $matches[1];
        }

        $documentNumber = strtoupper((string) ($row['no_dokumen'] ?? ''));
        if (preg_match('/^([A-Z0-9]+)-/u', $documentNumber, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function resolveActionCode(Department $department, string $keterangan, string $description): ?string
    {
        $departmentCode = strtoupper($department->code);
        $keteranganText = strtoupper($keterangan);
        $text = strtoupper($keterangan.' '.$description);

        return match ($departmentCode) {
            'ACC' => $this->containsAny($text, ['PIUTANG', 'REVENUE']) ? 'IN_ACC_PIUTANG_REVENUE'
                : ($this->containsAny($text, ['PAJAK']) ? 'OUT_ACC_PAJAK'
                : ($this->containsAny($text, ['OPERASIONAL', 'OPS']) ? 'OUT_ACC_OPS' : null)),
            'HR' => $this->containsAny($text, ['GAJI', 'BENEFIT']) ? 'OUT_HR_GAJI_BENEFIT'
                : ($this->containsAny($text, ['PINJAMAN']) ? 'OUT_HR_PEMBERIAN_PINJAMAN'
                : ($this->containsAny($text, ['OPERASIONAL', 'OPS', 'KAS BON']) ? 'OUT_HR_OPS' : null)),
            'CFC', 'FIN' => $this->resolveCfcActionCode($text),
            'TEP' => $this->containsAny($keteranganText, ['OPERASIONAL', 'OPS']) ? 'OUT_TEP_OPS'
                : ($this->containsAny($text, ['COST OF REVENUE', 'EVENT']) ? 'OUT_TEP_COST_OF_REVENUE'
                : ($this->containsAny($text, ['REVENUE']) ? 'IN_TEP_ESTIMASI_UPCOMING_REVENUE' : null)),
            default => $this->containsAny($text, ['OPERASIONAL', 'OPS']) ? $this->templateService->standardOpsActionCode($department) : null,
        };
    }

    private function resolveCfcActionCode(string $text): ?string
    {
        if ($this->containsAny($text, ['PENGEMBALIAN SUNTIKAN'])) {
            return 'OUT_CFC_PENGEMBALIAN_SUNTIKAN_MODAL';
        }

        if ($this->containsAny($text, ['SUNTIKAN MODAL'])) {
            return 'IN_CFC_SUNTIKAN_MODAL';
        }

        if ($this->containsAny($text, ['PENGEMBALIAN PINJAMAN'])) {
            return 'IN_CFC_PENERIMAAN_PENGEMBALIAN_PINJAMAN';
        }

        if ($this->containsAny($text, ['HUTANG USAHA'])) {
            return 'OUT_CFC_HUTANG_USAHA';
        }

        if ($this->containsAny($text, ['BUNGA', 'ANGSURAN'])) {
            return 'OUT_CFC_BUNGA_ANGSURAN';
        }

        if ($this->containsAny($text, ['CORPORATE'])) {
            return 'OUT_CFC_CORPORATE_EXPENSES';
        }

        return $this->containsAny($text, ['OPERASIONAL', 'OPS']) ? 'OUT_CFC_OPS' : null;
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
