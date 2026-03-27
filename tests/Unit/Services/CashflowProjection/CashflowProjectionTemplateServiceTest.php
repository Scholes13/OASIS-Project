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

    public function test_cfc_department_action_options_do_not_duplicate_operational_action(): void
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

        $actionCodes = array_column($service->actionOptionsForDepartment($department), 'code');

        $this->assertSame($actionCodes, array_values(array_unique($actionCodes)));
        $this->assertSame(1, count(array_filter($actionCodes, fn (string $code) => $code === 'OUT_CFC_OPS')));
    }
}
