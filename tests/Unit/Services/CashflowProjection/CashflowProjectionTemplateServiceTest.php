<?php

namespace Tests\Unit\Services\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Services\Modules\CashflowProjection\CashflowProjectionTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowProjectionTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_department_operational_label_uses_department_prefix(): void
    {
        $businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'is_active' => true,
        ]);

        $department = Department::create([
            'business_unit_id' => $businessUnit->id,
            'code' => 'BAS',
            'name' => 'Business Analysis Support',
            'is_active' => true,
        ]);

        $service = app(CashflowProjectionTemplateService::class);

        $actionCodes = array_column($service->actionOptionsForDepartment($department), 'code');
        $actionLabels = array_column($service->actionOptionsForDepartment($department), 'label');

        $this->assertSame(['OUT_BAS_OPS'], $actionCodes);
        $this->assertSame(['Operational Department BAS'], $actionLabels);
    }

    public function test_cfc_department_action_options_prefix_labels_by_department_code(): void
    {
        $businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'is_active' => true,
        ]);

        $department = Department::create([
            'business_unit_id' => $businessUnit->id,
            'code' => 'CFC',
            'name' => 'Core Finance',
            'is_active' => true,
        ]);

        $service = app(CashflowProjectionTemplateService::class);

        $options = $service->actionOptionsForDepartment($department);
        $actionLabels = array_column($options, 'label');

        $this->assertContains('ACC - Pajak', $actionLabels);
        $this->assertContains('TEP - Cost of Revenue dari Upcoming Revenue', $actionLabels);
        $this->assertContains('HR - Gaji & Benefit Karyawan', $actionLabels);
        $this->assertContains('CFC - Corporate Expense', $actionLabels);
        $this->assertContains('CFC - Operational Department CFC', $actionLabels);
    }
}
