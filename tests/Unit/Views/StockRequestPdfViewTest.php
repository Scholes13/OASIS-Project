<?php

namespace Tests\Unit\Views;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockRequestPdfViewTest extends TestCase
{
    public function test_it_renders_historical_names_when_user_relations_are_missing(): void
    {
        $stockRequest = (new StockRequest)->forceFill([
            'st_number' => 'ST/WNS/202601/001',
            'requester_name' => 'Historical Requester',
            'purpose' => 'Historical stock request',
            'date_of_request' => Carbon::parse('2026-01-12'),
            'status' => 'approved',
        ]);

        $stockRequest->setRelation('user', null);
        $stockRequest->setRelation('businessUnit', (new BusinessUnit)->forceFill([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
        ]));
        $stockRequest->setRelation('department', (new Department)->forceFill([
            'code' => 'SS',
            'name' => 'Strategic Sourcing',
        ]));
        $stockRequest->setRelation('items', collect());

        $approval = (new StockApproval)->forceFill([
            'id' => 1,
            'approval_type' => 'approval',
            'status' => 'approved',
            'step_order' => 1,
            'metadata' => [
                'approver_snapshot' => [
                    'name' => 'Historical Approver',
                    'department_code' => 'GA',
                ],
            ],
        ]);
        $approval->setRelation('approver', null);
        $stockRequest->setRelation('approvals', collect([$approval]));

        $html = view('purchasing.stock-requests.pdf-browser', [
            'stockRequest' => $stockRequest,
            'qrCodes' => ['approvals' => []],
        ])->render();

        $this->assertStringContainsString('Historical Requester', $html);
        $this->assertStringContainsString('Historical Approver', $html);
        $this->assertStringContainsString('GA', $html);
    }
}
