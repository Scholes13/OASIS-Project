<?php

namespace Database\Seeders\TEE;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TEE (Takshaka) Activity Type Seeder
 *
 * Uses updateOrCreate pattern to prevent duplicates and allow easy maintenance.
 */
class TEEActivityTypeSeeder extends Seeder
{
    private array $colors = [
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b',
        '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6',
    ];

    public function run(): void
    {
        $tee = BusinessUnit::where('code', 'TEE')->first();

        if (! $tee) {
            $this->command->error('Business Unit TEE tidak ditemukan!');

            return;
        }

        $this->seedDepartmentActivities($tee, 'TEE', $this->getTEEActivities());

        $this->command->info('TEE Activity Types seeded successfully!');
    }

    /**
     * Seed activities untuk satu departemen
     */
    private function seedDepartmentActivities(BusinessUnit $bu, string $deptCode, array $activities): void
    {
        $department = Department::where('business_unit_id', $bu->id)
            ->where('code', $deptCode)
            ->first();

        if (! $department) {
            $this->command->warn("Department {$deptCode} tidak ditemukan, skip...");

            return;
        }

        $colorIndex = 0;

        foreach ($activities as $sortOrder => $activity) {
            // Code dengan prefix dept: TEE_ADMINISTRATION
            $activityCode = "{$deptCode}_{$activity['code']}";

            $activityType = ActivityType::updateOrCreate(
                ['code' => $activityCode],
                [
                    'name' => $activity['name'],
                    'color' => $this->colors[$colorIndex % count($this->colors)],
                    'is_active' => true,
                    'sort_order' => $sortOrder + 1,
                ]
            );

            // Link ke department
            DB::table('department_activity_types')->updateOrInsert(
                [
                    'department_id' => $department->id,
                    'activity_type_id' => $activityType->id,
                ],
                [
                    'is_default' => $sortOrder === 0,
                    'sort_order' => $sortOrder + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Sub activities dengan prefix: TEE_ADMIN_DATA_ENTRY
            foreach ($activity['sub_activities'] as $subOrder => $sub) {
                $subCode = "{$deptCode}_{$sub['code']}";

                SubActivity::updateOrCreate(
                    [
                        'activity_type_id' => $activityType->id,
                        'code' => $subCode,
                    ],
                    [
                        'name' => $sub['name'],
                        'is_active' => true,
                        'sort_order' => $subOrder + 1,
                    ]
                );
            }

            $colorIndex++;
        }

        $count = count($activities);
        $this->command->info("  - {$deptCode}: {$count} activity types");
    }

    /**
     * TEE Department Activities
     */
    private function getTEEActivities(): array
    {
        return [
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_DATA_ENTRY', 'name' => 'Administration – Data Entry & Database'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_VERIFICATION', 'name' => 'Administration – Verification & Approval'],
                    ['code' => 'ADMIN_REQUESTS_PROCUREMENT', 'name' => 'Administration – Requests & Procurement'],
                    ['code' => 'ADMIN_REVIEW_CHECK', 'name' => 'Administration – Review & Checking'],
                    ['code' => 'ADMIN_HANDOVER', 'name' => 'Administration – Handover'],
                    ['code' => 'ADMIN_PAYMENT_PROCESSING', 'name' => 'Administration – Payment Processing'],
                ],
            ],
            [
                'code' => 'FINANCE_BILLING',
                'name' => 'Finance & Billing',
                'sub_activities' => [
                    ['code' => 'INVOICING', 'name' => 'Invoicing'],
                    ['code' => 'AR_MONITORING', 'name' => 'AR Monitoring'],
                    ['code' => 'RECONCILIATION', 'name' => 'Reconciliation'],
                    ['code' => 'TAX_DOCUMENT_SUPPORT', 'name' => 'Tax/Document Support'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EKSTERNAL', 'name' => 'Training – Eksternal'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
                ],
            ],
            [
                'code' => 'EXTERNAL_ACTIVITIES',
                'name' => 'External Activities',
                'sub_activities' => [
                    ['code' => 'EXT_ENGAGEMENTS', 'name' => 'External Activities – External Engagements'],
                ],
            ],
            [
                'code' => 'INTERNAL_ACTIVITIES',
                'name' => 'Internal Activities',
                'sub_activities' => [
                    ['code' => 'INT_EVENTS', 'name' => 'Internal Activities – Internal Events'],
                ],
            ],
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT', 'name' => 'Project'],
                ],
            ],
            [
                'code' => 'INTERNAL_PROJECT',
                'name' => 'Internal Project',
                'sub_activities' => [
                    ['code' => 'INTERNAL_PROJECT', 'name' => 'Internal Project'],
                ],
            ],
            [
                'code' => 'REPORTING',
                'name' => 'Reporting',
                'sub_activities' => [
                    ['code' => 'REPORTING', 'name' => 'Reporting'],
                ],
            ],
            [
                'code' => 'RESEARCH',
                'name' => 'Research',
                'sub_activities' => [
                    ['code' => 'RESEARCH', 'name' => 'Research'],
                ],
            ],
            [
                'code' => 'SURVEY_INSPECTION',
                'name' => 'Survey / Inspection',
                'sub_activities' => [
                    ['code' => 'SURVEY_INSPECTION', 'name' => 'Survey / Inspection'],
                ],
            ],
            [
                'code' => 'PURCHASING',
                'name' => 'Purchasing',
                'sub_activities' => [
                    ['code' => 'PURCHASING', 'name' => 'Purchasing'],
                ],
            ],
            [
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting – Internal'],
                    ['code' => 'MEETING_EXTERNAL', 'name' => 'Meeting – External'],
                ],
            ],
            [
                'code' => 'COSTING',
                'name' => 'Costing',
                'sub_activities' => [
                    ['code' => 'COSTING', 'name' => 'Costing'],
                ],
            ],
            [
                'code' => 'GENERAL_PREPARATION',
                'name' => 'General Preparation',
                'sub_activities' => [
                    ['code' => 'GENERAL_PREPARATION', 'name' => 'General Preparation'],
                ],
            ],
            [
                'code' => 'HOUSEKEEPING',
                'name' => 'Housekeeping',
                'sub_activities' => [
                    ['code' => 'CLEANLINESS_TIDINESS', 'name' => 'Cleanliness & Tidiness'],
                ],
            ],
            [
                'code' => 'HOSTING_PANTRY',
                'name' => 'Hosting & Pantry Services',
                'sub_activities' => [
                    ['code' => 'HOSTING_PANTRY_SERVICES', 'name' => 'Hosting & Pantry Services'],
                ],
            ],
            [
                'code' => 'PRODUCT_DEVELOPMENT',
                'name' => 'Product Development',
                'sub_activities' => [
                    ['code' => 'PRODUCT_DEVELOPMENT', 'name' => 'Product Development'],
                ],
            ],
            [
                'code' => 'SUPPORT_ACTIVITIES',
                'name' => 'Support Activities',
                'sub_activities' => [
                    ['code' => 'OFFICE_SUPPORT', 'name' => 'Office Support'],
                ],
            ],
            [
                'code' => 'GENERAL_STOCK',
                'name' => 'General Stock',
                'sub_activities' => [
                    ['code' => 'GENERAL_STOCK', 'name' => 'General Stock'],
                ],
            ],
            [
                'code' => 'SALES_CALL',
                'name' => 'Sales Call',
                'sub_activities' => [
                    ['code' => 'SALES_CALL', 'name' => 'Sales Call'],
                ],
            ],
            [
                'code' => 'MARKETING',
                'name' => 'Marketing',
                'sub_activities' => [
                    ['code' => 'MARKETING', 'name' => 'Marketing'],
                ],
            ],
            [
                'code' => 'FOLLOW_UP',
                'name' => 'Follow Up',
                'sub_activities' => [
                    ['code' => 'FOLLOW_UP', 'name' => 'Follow Up'],
                ],
            ],
            [
                'code' => 'RECRUITMENT_PROCESS',
                'name' => 'Recruitment Process',
                'sub_activities' => [
                    ['code' => 'INTERVIEW', 'name' => 'Interview'],
                ],
            ],
            [
                'code' => 'WELLBEING',
                'name' => 'Wellbeing',
                'sub_activities' => [
                    ['code' => 'WELLBEING_ACTIVITIES', 'name' => 'Wellbeing – Activities'],
                ],
            ],
            [
                'code' => 'ACTION_PLAN',
                'name' => 'Action Plan',
                'sub_activities' => [
                    ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
                ],
            ],
            [
                'code' => 'MAINTENANCE',
                'name' => 'Maintenance',
                'sub_activities' => [
                    ['code' => 'MAINTENANCE_VEHICLE', 'name' => 'Maintenance – Vehicle'],
                    ['code' => 'MAINTENANCE_BUILDING', 'name' => 'Maintenance – Building'],
                    ['code' => 'MAINTENANCE_LANDSCAPE', 'name' => 'Maintenance – Landscape'],
                    ['code' => 'MAINTENANCE_OFFICE_EQUIPMENT', 'name' => 'Maintenance – Office Equipment'],
                ],
            ],
        ];
    }
}
