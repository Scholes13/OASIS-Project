<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();
        
        if (!$wns) {
            return;
        }

        // Get key approvers
        $hodFin = User::join('user_business_units', 'users.id', '=', 'user_business_units.user_id')
            ->join('departments', 'user_business_units.department_id', '=', 'departments.id')
            ->where('departments.code', 'FIN')
            ->where('departments.business_unit_id', $wns->id)
            ->where('user_business_units.role', 'hod')
            ->first();

        $hodProc = User::join('user_business_units', 'users.id', '=', 'user_business_units.user_id')
            ->join('departments', 'user_business_units.department_id', '=', 'departments.id')
            ->where('departments.code', 'PROC')
            ->where('departments.business_unit_id', $wns->id)
            ->where('user_business_units.role', 'hod')
            ->first();

        $bod = User::join('user_business_units', 'users.id', '=', 'user_business_units.user_id')
            ->where('user_business_units.business_unit_id', $wns->id)
            ->where('user_business_units.role', 'bod')
            ->first();

        if (!$hodFin || !$hodProc || !$bod) {
            return;
        }

        $workflows = [
            // Standard PR Workflow (< IDR 10,000,000)
            [
                'name' => 'Standard PR Approval',
                'description' => 'Standard approval workflow for PRs under IDR 10,000,000',
                'business_unit_id' => $wns->id,
                'module_type' => 'purchase_request',
                'approval_steps' => [
                    [
                        'step' => 1,
                        'approver_id' => $hodFin->id,
                        'approver_name' => $hodFin->name,
                        'approver_role' => 'HOD Finance',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                    [
                        'step' => 2,
                        'approver_id' => $hodProc->id,
                        'approver_name' => $hodProc->name,
                        'approver_role' => 'HOD Procurement',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                ],
                'is_sequential' => true,
                'is_default' => true,
                'is_active' => true,
                'conditions' => [
                    [
                        'field' => 'total_amount',
                        'operator' => '<',
                        'value' => 10000000,
                    ]
                ],
            ],

            // High Value PR Workflow (>= IDR 10,000,000)
            [
                'name' => 'High Value PR Approval',
                'description' => 'Approval workflow for high-value PRs (>= IDR 10,000,000)',
                'business_unit_id' => $wns->id,
                'module_type' => 'purchase_request',
                'approval_steps' => [
                    [
                        'step' => 1,
                        'approver_id' => $hodFin->id,
                        'approver_name' => $hodFin->name,
                        'approver_role' => 'HOD Finance',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                    [
                        'step' => 2,
                        'approver_id' => $hodProc->id,
                        'approver_name' => $hodProc->name,
                        'approver_role' => 'HOD Procurement',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                    [
                        'step' => 3,
                        'approver_id' => $bod->id,
                        'approver_name' => $bod->name,
                        'approver_role' => 'Board of Director',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                ],
                'is_sequential' => true,
                'is_default' => false,
                'is_active' => true,
                'conditions' => [
                    [
                        'field' => 'total_amount',
                        'operator' => '>=',
                        'value' => 10000000,
                    ]
                ],
            ],

            // Emergency PR Workflow
            [
                'name' => 'Emergency PR Approval',
                'description' => 'Fast-track approval workflow for emergency PRs',
                'business_unit_id' => $wns->id,
                'module_type' => 'purchase_request',
                'approval_steps' => [
                    [
                        'step' => 1,
                        'approver_id' => $bod->id,
                        'approver_name' => $bod->name,
                        'approver_role' => 'Board of Director',
                        'required' => true,
                        'can_delegate' => true,
                    ],
                ],
                'is_sequential' => true,
                'is_default' => false,
                'is_active' => true,
                'conditions' => [
                    [
                        'field' => 'is_emergency',
                        'operator' => '=',
                        'value' => true,
                    ]
                ],
            ],

            // IT Equipment PR Workflow
            [
                'name' => 'IT Equipment PR Approval',
                'description' => 'Specialized approval workflow for IT equipment purchases',
                'business_unit_id' => $wns->id,
                'module_type' => 'purchase_request',
                'approval_steps' => [
                    [
                        'step' => 1,
                        'approver_id' => User::join('user_business_units', 'users.id', '=', 'user_business_units.user_id')
                            ->join('departments', 'user_business_units.department_id', '=', 'departments.id')
                            ->where('departments.code', 'IT')
                            ->where('departments.business_unit_id', $wns->id)
                            ->where('user_business_units.role', 'hod')
                            ->first()->id ?? $hodFin->id,
                        'approver_name' => 'HOD IT',
                        'approver_role' => 'HOD Information Technology',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                    [
                        'step' => 2,
                        'approver_id' => $hodFin->id,
                        'approver_name' => $hodFin->name,
                        'approver_role' => 'HOD Finance',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                    [
                        'step' => 3,
                        'approver_id' => $hodProc->id,
                        'approver_name' => $hodProc->name,
                        'approver_role' => 'HOD Procurement',
                        'required' => true,
                        'can_delegate' => false,
                    ],
                ],
                'is_sequential' => true,
                'is_default' => false,
                'is_active' => true,
                'conditions' => [
                    [
                        'field' => 'department_code',
                        'operator' => '=',
                        'value' => 'IT',
                    ]
                ],
            ],
        ];

        foreach ($workflows as $workflowData) {
            ApprovalWorkflow::firstOrCreate(
                [
                    'business_unit_id' => $workflowData['business_unit_id'],
                    'module_type' => $workflowData['module_type'],
                    'name' => $workflowData['name'],
                ],
                $workflowData
            );
        }
    }
}