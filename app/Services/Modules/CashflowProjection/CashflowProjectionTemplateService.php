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
        'OUT_ACC_OPS' => ['label' => 'Operational ACC', 'flow_type' => 'out'],

        'OUT_HR_GAJI_BENEFIT' => ['label' => 'Gaji & Benefit', 'flow_type' => 'out'],
        'OUT_HR_PEMBERIAN_PINJAMAN' => ['label' => 'Pemberian Pinjaman', 'flow_type' => 'out'],
        'OUT_HR_OPS' => ['label' => 'Operational HR', 'flow_type' => 'out'],

        'IN_CFC_PENERIMAAN_PENGEMBALIAN_PINJAMAN' => ['label' => 'Penerimaan Pengembalian Pinjaman', 'flow_type' => 'in'],
        'IN_CFC_SUNTIKAN_MODAL' => ['label' => 'Suntikan Modal', 'flow_type' => 'in'],
        'OUT_CFC_CORPORATE_EXPENSES' => ['label' => 'Corporate Expenses', 'flow_type' => 'out'],
        'OUT_CFC_BUNGA_ANGSURAN' => ['label' => 'Bunga & Angsuran', 'flow_type' => 'out'],
        'OUT_CFC_HUTANG_USAHA' => ['label' => 'Hutang Usaha', 'flow_type' => 'out'],
        'OUT_CFC_PENGEMBALIAN_SUNTIKAN_MODAL' => ['label' => 'Pengembalian Suntikan Modal', 'flow_type' => 'out'],
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
            'hr' => [
                'OUT_HR_GAJI_BENEFIT',
                'OUT_HR_PEMBERIAN_PINJAMAN',
                'OUT_HR_OPS',
            ],
            'cfc' => [
                'IN_CFC_PENERIMAAN_PENGEMBALIAN_PINJAMAN',
                'IN_CFC_SUNTIKAN_MODAL',
                'OUT_CFC_CORPORATE_EXPENSES',
                'OUT_CFC_BUNGA_ANGSURAN',
                'OUT_CFC_HUTANG_USAHA',
                'OUT_CFC_PENGEMBALIAN_SUNTIKAN_MODAL',
            ],
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
                'label' => $meta['label'],
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
                'label' => 'Operational '.$department->code,
                'flow_type' => 'out',
            ];
        }

        return null;
    }

    public function templateTypeForDepartment(Department $department): string
    {
        $code = strtoupper($department->code);

        return match ($code) {
            'ACC' => 'acc',
            'HR' => 'hr',
            'CFC', 'FIN' => 'cfc',
            default => 'standard',
        };
    }

    public function standardOpsActionCode(Department $department): string
    {
        return 'OUT_'.$this->normalizeCode($department->code).'_OPS';
    }

    private function normalizeCode(string $value): string
    {
        $upper = strtoupper($value);

        return preg_replace('/[^A-Z0-9]+/', '_', $upper) ?? $upper;
    }
}
