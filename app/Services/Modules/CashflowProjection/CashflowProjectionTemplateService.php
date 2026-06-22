<?php

namespace App\Services\Modules\CashflowProjection;

use App\Models\Core\Department;

class CashflowProjectionTemplateService
{
    /**
     * @var array<string, array{label: string, flow_type: string}>
     */
    private const ACTIONS = [
        'IN_ACC_PIUTANG_REVENUE' => ['label' => 'Piutang & Revenue', 'flow_type' => 'in'],
        'OUT_ACC_PAJAK' => ['label' => 'Pajak', 'flow_type' => 'out'],
        'OUT_ACC_OPS' => ['label' => 'Operational Department ACC', 'flow_type' => 'out'],

        'IN_TEP_ESTIMASI_UPCOMING_REVENUE' => ['label' => 'Estimasi Penerimaan dari Upcoming Revenue', 'flow_type' => 'in'],
        'OUT_TEP_COST_OF_REVENUE' => ['label' => 'Cost of Revenue dari Upcoming Revenue', 'flow_type' => 'out'],
        'OUT_TEP_OPS' => ['label' => 'Operational Department TEP', 'flow_type' => 'out'],

        'OUT_HR_GAJI_BENEFIT' => ['label' => 'Gaji & Benefit Karyawan', 'flow_type' => 'out'],
        'OUT_HR_OPS' => ['label' => 'Operational Department HR', 'flow_type' => 'out'],
        'OUT_HR_PEMBERIAN_PINJAMAN' => ['label' => 'Pemberian Pinjaman', 'flow_type' => 'out'],

        'IN_CFC_SUNTIKAN_MODAL' => ['label' => 'Suntikan Modal', 'flow_type' => 'in'],
        'IN_CFC_PENERIMAAN_PENGEMBALIAN_PINJAMAN' => ['label' => 'Penerimaan dari Pengembalian Pinjaman', 'flow_type' => 'in'],
        'OUT_CFC_CORPORATE_EXPENSES' => ['label' => 'Corporate Expense', 'flow_type' => 'out'],
        'OUT_CFC_BUNGA_ANGSURAN' => ['label' => 'Bunga & Angsuran', 'flow_type' => 'out'],
        'OUT_CFC_PENGEMBALIAN_SUNTIKAN_MODAL' => ['label' => 'Pengembalian Suntikan Modal', 'flow_type' => 'out'],
        'OUT_CFC_HUTANG_USAHA' => ['label' => 'Hutang Usaha', 'flow_type' => 'out'],
        'OUT_CFC_OPS' => ['label' => 'Operational Department CFC', 'flow_type' => 'out'],
    ];

    /**
     * @return array<int, string>
     */
    public function allowedActionCodesForDepartment(Department $department): array
    {
        $template = $this->templateTypeForDepartment($department);

        return match ($template) {
            'acc' => [
                'IN_ACC_PIUTANG_REVENUE',
                'OUT_ACC_PAJAK',
                'OUT_ACC_OPS',
            ],
            'tep' => [
                'IN_TEP_ESTIMASI_UPCOMING_REVENUE',
                'OUT_TEP_COST_OF_REVENUE',
                'OUT_TEP_OPS',
            ],
            'hr' => [
                'OUT_HR_GAJI_BENEFIT',
                'OUT_HR_OPS',
                'OUT_HR_PEMBERIAN_PINJAMAN',
            ],
            'cfc' => array_values(array_unique(array_merge(
                array_keys(self::ACTIONS),
                [$this->standardOpsActionCode($department)],
            ))),
            default => [$this->standardOpsActionCode($department)],
        };
    }

    /**
     * @return array<int, array{code: string, label: string, flow_type: string}>
     */
    public function actionOptionsForDepartment(Department $department): array
    {
        $options = [];

        foreach ($this->allowedActionCodesForDepartment($department) as $code) {
            $meta = $this->metaForActionCode($code, $department);

            if (! $meta) {
                continue;
            }

            $options[] = [
                'code' => $code,
                'label' => $this->displayLabelForAction($code, $department) ?? $meta['label'],
                'flow_type' => $meta['flow_type'],
            ];
        }

        return $options;
    }

    public function isActionAllowedForDepartment(string $actionCode, Department $department): bool
    {
        return in_array($actionCode, $this->allowedActionCodesForDepartment($department), true);
    }

    /**
     * @return array{label: string, flow_type: string}|null
     */
    public function metaForActionCode(string $actionCode, ?Department $department = null): ?array
    {
        if (isset(self::ACTIONS[$actionCode])) {
            return self::ACTIONS[$actionCode];
        }

        if ($department && $actionCode === $this->standardOpsActionCode($department)) {
            return [
                'label' => 'Operational Department '.$department->code,
                'flow_type' => 'out',
            ];
        }

        return null;
    }

    public function displayLabelForAction(string $actionCode, ?Department $department = null): ?string
    {
        $meta = $this->metaForActionCode($actionCode, $department);

        if (! $meta) {
            return null;
        }

        $prefix = $this->actionPrefixFromCode($actionCode, $department);

        return $prefix ? $prefix.' - '.$meta['label'] : $meta['label'];
    }

    public function templateTypeForDepartment(Department $department): string
    {
        $code = strtoupper($department->code);

        return match ($code) {
            'ACC' => 'acc',
            'TEP' => 'tep',
            'HR' => 'hr',
            'CFC', 'FIN' => 'cfc',
            default => 'standard',
        };
    }

    public function standardOpsActionCode(Department $department): string
    {
        return 'OUT_'.$this->normalizeCode($department->code).'_OPS';
    }

    private function actionPrefixFromCode(string $actionCode, ?Department $department = null): ?string
    {
        $segments = explode('_', $actionCode, 4);

        return $segments[1] ?? $department?->code;
    }

    private function normalizeCode(string $value): string
    {
        $upper = strtoupper($value);

        return preg_replace('/[^A-Z0-9]+/', '_', $upper) ?? $upper;
    }
}
