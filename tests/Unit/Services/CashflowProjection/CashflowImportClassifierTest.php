<?php

namespace Tests\Unit\Services\CashflowProjection;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Services\Modules\CashflowProjection\Import\CashflowImportClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowImportClassifierTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $businessUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'is_active' => true,
        ]);

        foreach (['ACC', 'HR', 'CFC', 'TEP', 'GA'] as $code) {
            Department::create([
                'business_unit_id' => $this->businessUnit->id,
                'code' => $code,
                'name' => $code,
                'is_active' => true,
            ]);
        }
    }

    public function test_detects_department_from_description_pattern(): void
    {
        $result = app(CashflowImportClassifier::class)->classify([
            'business_unit_code' => 'WNS',
            'description' => 'WNS - TEP - FAMTRIP JCWF - biaya event',
            'no_dokumen' => null,
            'keterangan' => 'OPERASIONAL',
        ]);

        $this->assertSame('ready', $result['status']);
        $this->assertSame('TEP', $result['department_code']);
        $this->assertSame('OUT_TEP_OPS', $result['action_code']);
        $this->assertSame('out', $result['flow_type']);
    }

    public function test_detects_department_from_document_number_prefix(): void
    {
        $result = app(CashflowImportClassifier::class)->classify([
            'business_unit_code' => 'WNS',
            'description' => 'Pengajuan kasbon movieday',
            'no_dokumen' => 'HR-02/202605/0016',
            'keterangan' => 'KAS BON OPERASIONAL',
        ]);

        $this->assertSame('ready', $result['status']);
        $this->assertSame('HR', $result['department_code']);
        $this->assertSame('OUT_HR_OPS', $result['action_code']);
        $this->assertSame('out', $result['flow_type']);
    }

    public function test_classifies_known_action_keywords(): void
    {
        $classifier = app(CashflowImportClassifier::class);

        $this->assertSame('OUT_ACC_PAJAK', $classifier->classify($this->row('ACC', 'PAJAK'))['action_code']);
        $this->assertSame('IN_ACC_PIUTANG_REVENUE', $classifier->classify($this->row('ACC', 'PIUTANG REVENUE'))['action_code']);
        $this->assertSame('OUT_HR_GAJI_BENEFIT', $classifier->classify($this->row('HR', 'GAJI BENEFIT'))['action_code']);
        $this->assertSame('IN_CFC_SUNTIKAN_MODAL', $classifier->classify($this->row('CFC', 'SUNTIKAN MODAL'))['action_code']);
        $this->assertSame('OUT_CFC_HUTANG_USAHA', $classifier->classify($this->row('CFC', 'HUTANG USAHA'))['action_code']);
        $this->assertSame('OUT_TEP_COST_OF_REVENUE', $classifier->classify($this->row('TEP', 'EVENT COST OF REVENUE'))['action_code']);
        $this->assertSame('OUT_GA_OPS', $classifier->classify($this->row('GA', 'OPERASIONAL'))['action_code']);
    }

    public function test_unknown_department_or_action_returns_need_review(): void
    {
        $classifier = app(CashflowImportClassifier::class);

        $missingDepartment = $classifier->classify([
            'business_unit_code' => 'WNS',
            'description' => 'Biaya tanpa kode department',
            'no_dokumen' => null,
            'keterangan' => 'OPERASIONAL',
        ]);
        $this->assertSame('need_review', $missingDepartment['status']);
        $this->assertSame('department_code', $missingDepartment['errors'][0]['field']);

        $missingAction = $classifier->classify($this->row('ACC', 'UNKNOWN CATEGORY'));
        $this->assertSame('need_review', $missingAction['status']);
        $this->assertSame('action_code', $missingAction['errors'][0]['field']);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $departmentCode, string $keterangan): array
    {
        return [
            'business_unit_code' => 'WNS',
            'description' => 'WNS - '.$departmentCode.' - Sample entry',
            'no_dokumen' => null,
            'keterangan' => $keterangan,
        ];
    }
}
