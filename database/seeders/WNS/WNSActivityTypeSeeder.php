<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated This seeder is deprecated and kept for reference only.
 *
 * Use GlobalActivityTypeSeeder and DepartmentActivityAssignmentSeeder instead.
 *
 * The new architecture uses global activity types without department prefix:
 * - GlobalActivityTypeSeeder: Creates master activity types (LEAVE, TRAINING, etc.)
 * - DepartmentActivityAssignmentSeeder: Assigns activity types to departments
 *
 * Old approach (this seeder):
 * - Code: ACC_LEAVE (with department prefix)
 * - Name: Leave (user-facing)
 *
 * New approach:
 * - Code: LEAVE (no prefix, global)
 * - Name: Leave (user-facing)
 * - Assignment via department_activity_types pivot table
 * @see \Database\Seeders\GlobalActivityTypeSeeder
 * @see \Database\Seeders\DepartmentActivityAssignmentSeeder
 */
class WNSActivityTypeSeeder extends Seeder
{
    private array $colors = [
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b',
        '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6',
    ];

    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();

        if (! $wns) {
            $this->command->error('Business Unit WNS tidak ditemukan!');

            return;
        }

        // Seed per department
        $this->seedDepartmentActivities($wns, 'ACC', $this->getACCActivities());
        $this->seedDepartmentActivities($wns, 'ACS', $this->getACSActivities());
        $this->seedDepartmentActivities($wns, 'BAS', $this->getBASActivities());
        $this->seedDepartmentActivities($wns, 'BID', $this->getBIDActivities());
        $this->seedDepartmentActivities($wns, 'CFC', $this->getCFCActivities());
        $this->seedDepartmentActivities($wns, 'GA', $this->getGAActivities());
        $this->seedDepartmentActivities($wns, 'HR', $this->getHRActivities());
        $this->seedDepartmentActivities($wns, 'PD', $this->getPDActivities());
        $this->seedDepartmentActivities($wns, 'SO', $this->getSOActivities());
        $this->seedDepartmentActivities($wns, 'SS', $this->getSSActivities());
        $this->seedDepartmentActivities($wns, 'TEP', $this->getTEPActivities());

        $this->command->info('WNS Activity Types seeded successfully!');
    }

    /**
     * Seed activities untuk satu departemen
     */
    private function seedDepartmentActivities(BusinessUnit $wns, string $deptCode, array $activities): void
    {
        $department = Department::where('business_unit_id', $wns->id)
            ->where('code', $deptCode)
            ->first();

        if (! $department) {
            $this->command->warn("Department {$deptCode} tidak ditemukan, skip...");

            return;
        }

        $colorIndex = 0;

        foreach ($activities as $sortOrder => $activity) {
            // Code dengan prefix dept: ACC_LEAVE, ACC_TRAINING
            $activityCode = "{$deptCode}_{$activity['code']}";

            $activityType = ActivityType::updateOrCreate(
                ['code' => $activityCode],
                [
                    'name' => $activity['name'], // User lihat: "Leave", "Training"
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

            // Sub activities dengan prefix: ACC_LEAVE_SICK
            foreach ($activity['sub_activities'] as $subOrder => $sub) {
                $subCode = "{$deptCode}_{$sub['code']}";

                SubActivity::updateOrCreate(
                    [
                        'activity_type_id' => $activityType->id,
                        'code' => $subCode,
                    ],
                    [
                        'name' => $sub['name'], // User lihat nama bersih
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
     * ACC (Accounting) Activities
     */
    private function getACCActivities(): array
    {
        return [
            [
                'code' => 'ACTION_PLAN',
                'name' => 'Action Plan',
                'sub_activities' => [
                    ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
                ],
            ],
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_REVIEW_CHECK', 'name' => 'Administration – Review & Checking'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_TAX', 'name' => 'Administration – Tax'],
                    ['code' => 'ADMIN_PNL', 'name' => 'Administration – PNL'],
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
                'code' => 'WELLBEING',
                'name' => 'Wellbeing',
                'sub_activities' => [
                    ['code' => 'WELLBEING_ACTIVITIES', 'name' => 'Wellbeing – Activities'],
                ],
            ],
            [
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
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
                'code' => 'FINANCE_BILLING',
                'name' => 'Finance & Billing',
                'sub_activities' => [
                    ['code' => 'INVOICING', 'name' => 'Invoicing'],
                    ['code' => 'AR_MONITORING', 'name' => 'AR Monitoring'],
                    ['code' => 'RECONCILIATION', 'name' => 'Reconciliation'],
                    ['code' => 'PAYMENT_KLIEN', 'name' => 'Payment Klien'],
                ],
            ],
        ];
    }

    /**
     * ACS (Art & Creative Support) Activities
     */
    private function getACSActivities(): array
    {
        return [
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_DESIGN_CORPORATE', 'name' => 'Project – Design untuk Project Corporate'],
                    ['code' => 'PROJECT_DESIGN_TRAVEL', 'name' => 'Project – Design untuk Project Travel'],
                    ['code' => 'PROJECT_DESIGN_CREATIVE', 'name' => 'Project – Design untuk Project Creative'],
                    ['code' => 'PROJECT_DESIGN_WELLNESS', 'name' => 'Project – Design untuk Project Wellness'],
                    ['code' => 'PROJECT_DESIGN_TRAINING', 'name' => 'Project – Design untuk Project Training'],
                    ['code' => 'PROJECT_DESIGN_TAKSHAKA', 'name' => 'Project – Design untuk Project Takshaka'],
                    ['code' => 'PROJECT_INCHARGE', 'name' => 'Project – Incharge'],
                    ['code' => 'PROJECT_SURVEY_VENUE', 'name' => 'Project – Survey Venue'],
                    ['code' => 'PROJECT_BRIEFING_TEAM', 'name' => 'Project – Briefing Team Project'],
                ],
            ],
            [
                'code' => 'FOLLOW_UP_LEAD',
                'name' => 'Follow Up Lead',
                'sub_activities' => [
                    ['code' => 'LEAD_BRAINSTORMING', 'name' => 'Lead Follow Up – Brainstorming'],
                    ['code' => 'LEAD_DESIGN', 'name' => 'Lead Follow Up – Design'],
                    ['code' => 'LEAD_MEETING', 'name' => 'Lead Follow Up – Meeting'],
                    ['code' => 'LEAD_COSTING_VENDOR', 'name' => 'Lead Follow Up – Costing & Vendor Follow-up'],
                ],
            ],
            [
                'code' => 'INTERNAL_DESIGN',
                'name' => 'Internal Design',
                'sub_activities' => [
                    ['code' => 'INTERNAL_DESIGN_FOLLOWUP', 'name' => 'Internal Design – Follow Up Internal'],
                    ['code' => 'INTERNAL_DESIGN_VENDOR_SS', 'name' => 'Internal Design – Follow Up Vendor or SS'],
                ],
            ],
            [
                'code' => 'MARKETING',
                'name' => 'Marketing',
                'sub_activities' => [
                    ['code' => 'MARKETING_DESIGN', 'name' => 'Marketing – Design'],
                    ['code' => 'MARKETING_CONTENT_CREATION', 'name' => 'Marketing – Content Creation'],
                    ['code' => 'MARKETING_MATERIAL_SUPPORT', 'name' => 'Marketing – Marketing Material Support'],
                    ['code' => 'MARKETING_DIGITAL_CHANNEL', 'name' => 'Marketing – Digital Channel Management'],
                    ['code' => 'MARKETING_CONTENT_UPDATE', 'name' => 'Marketing – Content Update & Optimization'],
                    ['code' => 'MARKETING_COLLATERAL_UPDATE', 'name' => 'Marketing – Marketing Collateral Update'],
                ],
            ],
            [
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_PROJECT', 'name' => 'Meeting – Project'],
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting – Internal'],
                    ['code' => 'MEETING_VENDOR', 'name' => 'Meeting – Vendor'],
                    ['code' => 'MEETING_EXTERNAL', 'name' => 'Meeting – External'],
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
                'code' => 'EXTERNAL_ACTIVITIES',
                'name' => 'External Activities',
                'sub_activities' => [
                    ['code' => 'EXT_ENGAGEMENTS', 'name' => 'External Activities – External Engagements'],
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
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_DATA_ENTRY', 'name' => 'Administration – Data Entry & Database'],
                    ['code' => 'ADMIN_REQUESTS_PROCUREMENT', 'name' => 'Administration – Requests & Procurement'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_GENERAL', 'name' => 'Administration – General'],
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
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
                ],
            ],
            [
                'code' => 'PERFORMANCE_MANAGEMENT',
                'name' => 'Performance Management',
                'sub_activities' => [
                    ['code' => 'APPRAISAL', 'name' => 'Appraisal'],
                ],
            ],
            [
                'code' => 'PEOPLE_DEVELOPMENT',
                'name' => 'People Development',
                'sub_activities' => [
                    ['code' => 'COUNSELING', 'name' => 'Counseling'],
                ],
            ],
        ];
    }

    /**
     * BAS (Business & Administrative Services) Activities
     */
    private function getBASActivities(): array
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
                    ['code' => 'ADMIN_TEMPLATE_PREP', 'name' => 'Administration – Template Preparation'],
                    ['code' => 'ADMIN_HANDOVER', 'name' => 'Administration – Handover'],
                    ['code' => 'ADMIN_PAYMENT_PROCESSING', 'name' => 'Administration – Payment Processing'],
                ],
            ],
            [
                'code' => 'IT_MANAGEMENT',
                'name' => 'IT Management',
                'sub_activities' => [
                    ['code' => 'IT_INSTALLATION', 'name' => 'IT – Installation'],
                    ['code' => 'IT_MAINTENANCE', 'name' => 'IT – Maintenance'],
                    ['code' => 'IT_SUPPORT_SERVICES', 'name' => 'IT – Support Services'],
                ],
            ],
            [
                'code' => 'PROCUREMENT',
                'name' => 'Procurement',
                'sub_activities' => [
                    ['code' => 'PROCUREMENT_PREP_COLLECTING', 'name' => 'Procurement – Preparation & Collecting'],
                    ['code' => 'PROCUREMENT_REGISTRATION', 'name' => 'Procurement – Registration'],
                    ['code' => 'PROCUREMENT_TENDERING', 'name' => 'Procurement – Tendering'],
                    ['code' => 'PROCUREMENT_VERIFICATION', 'name' => 'Procurement – Verification & Approval'],
                    ['code' => 'PROCUREMENT_UPDATE_DATA', 'name' => 'Procurement – Update Data'],
                ],
            ],
            [
                'code' => 'WEBSITE',
                'name' => 'Website',
                'sub_activities' => [
                    ['code' => 'WEBSITE_CONCEPT_DESIGN', 'name' => 'Website – Concept & Visual Design'],
                    ['code' => 'WEBSITE_FEATURE_ENHANCEMENT', 'name' => 'Website – Feature Enhancement'],
                    ['code' => 'WEBSITE_BUG_FIXING', 'name' => 'Website – Bug Fixing'],
                    ['code' => 'WEBSITE_INTERNAL_DEPLOY', 'name' => 'Website – Internal Deployment'],
                    ['code' => 'WEBSITE_PUBLIC_DEPLOY', 'name' => 'Website – Public Deployment'],
                    ['code' => 'WEBSITE_QA', 'name' => 'Website – Quality Assurance'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
                ],
            ],
            [
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting – Internal'],
                    ['code' => 'MEETING_EXTERNAL', 'name' => 'Meeting – External'],
                    ['code' => 'COORDINATION_INTERNAL', 'name' => 'Coordination – Internal'],
                    ['code' => 'COORDINATION_EXTERNAL', 'name' => 'Coordination – External'],
                    ['code' => 'MEETING_BRAINSTORMING', 'name' => 'Meeting – Brainstorming'],
                ],
            ],
            [
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
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
                'code' => 'BUSINESS_TRAVEL',
                'name' => 'Business Travel',
                'sub_activities' => [
                    ['code' => 'TRAVEL_DOMESTIC', 'name' => 'Business Travel – Domestic'],
                    ['code' => 'TRAVEL_INTERNATIONAL', 'name' => 'Business Travel – International'],
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
                'code' => 'ACTION_PLAN',
                'name' => 'Action Plan',
                'sub_activities' => [
                    ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
                    ['code' => 'ACTION_PLAN_REVIEW', 'name' => 'Action Plan – Review'],
                    ['code' => 'ACTION_PLAN_VERIFICATION', 'name' => 'Action Plan – Verification'],
                ],
            ],
            [
                'code' => 'DATA_PROCESSING',
                'name' => 'Data Processing',
                'sub_activities' => [
                    ['code' => 'DATA_PROCESSING', 'name' => 'Data Processing'],
                ],
            ],
            [
                'code' => 'SUPPORT_ACTIVITIES',
                'name' => 'Support Activities',
                'sub_activities' => [
                    ['code' => 'SUPPORT_DEPARTMENT', 'name' => 'Support – Department'],
                    ['code' => 'SUPPORT_BOD', 'name' => 'Support – BOD'],
                    ['code' => 'SUPPORT_BUSINESS_UNIT', 'name' => 'Support – Business Unit'],
                ],
            ],
            [
                'code' => 'PERFORMANCE_MANAGEMENT',
                'name' => 'Performance Management',
                'sub_activities' => [
                    ['code' => 'APPRAISAL', 'name' => 'Appraisal'],
                ],
            ],
            [
                'code' => 'PEOPLE_DEVELOPMENT',
                'name' => 'People Development',
                'sub_activities' => [
                    ['code' => 'COUNSELING', 'name' => 'Counseling'],
                ],
            ],
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_INCHARGE', 'name' => 'Project – Incharge'],
                    ['code' => 'PROJECT_ADMINISTRATION', 'name' => 'Project – Administration'],
                ],
            ],
            [
                'code' => 'WELLBEING',
                'name' => 'Wellbeing',
                'sub_activities' => [
                    ['code' => 'WELLBEING_ACTIVITIES', 'name' => 'Wellbeing – Activities'],
                    ['code' => 'WELLBEING_REPORTING', 'name' => 'Wellbeing – Reporting'],
                ],
            ],
            [
                'code' => 'QMS_ISO',
                'name' => 'Quality Management System (ISO)',
                'sub_activities' => [
                    ['code' => 'ISO_COMPLIANCE', 'name' => 'ISO Compliance & Implementation'],
                    ['code' => 'ISO_INTERNAL_AUDIT', 'name' => 'Internal Audit Activities'],
                    ['code' => 'ISO_EXTERNAL_AUDIT', 'name' => 'External Audit Activities'],
                    ['code' => 'ISO_DOCUMENTATION', 'name' => 'ISO Documentation & Control'],
                ],
            ],
        ];
    }

    /**
     * BID (Business Innovation Development) Activities
     */
    private function getBIDActivities(): array
    {
        return [
            [
                'code' => 'ACTION_PLAN',
                'name' => 'Action Plan',
                'sub_activities' => [
                    ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
                ],
            ],
            [
                'code' => 'RESEARCH',
                'name' => 'Research',
                'sub_activities' => [
                    ['code' => 'RESEARCH_DEPARTMENT', 'name' => 'Research – Department'],
                    ['code' => 'RESEARCH_EXPAND_BUSINESS', 'name' => 'Research – Expand Business'],
                    ['code' => 'RESEARCH_SOURCING_NEW_BISNIS', 'name' => 'Research – Sourcing New Bisnis'],
                ],
            ],
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_VERIFICATION', 'name' => 'Administration – Verification & Approval'],
                    ['code' => 'ADMIN_REVIEW_CHECK', 'name' => 'Administration – Review & Checking'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_REQUESTS_PROCUREMENT', 'name' => 'Administration – Requests & Procurement'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
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
                'code' => 'ENTERTAINMENT',
                'name' => 'Entertainment',
                'sub_activities' => [
                    ['code' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
                ],
            ],
            [
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
                ],
            ],
            [
                'code' => 'DATA_PROCESSING',
                'name' => 'Data Processing',
                'sub_activities' => [
                    ['code' => 'DATA_PROCESSING', 'name' => 'Data Processing'],
                ],
            ],
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_ECO_FRIENDLY', 'name' => 'Project – Eco Friendly Product'],
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
                'code' => 'BUSINESS_TRAVEL',
                'name' => 'Business Travel',
                'sub_activities' => [
                    ['code' => 'TRAVEL_DOMESTIC', 'name' => 'Business Travel – Domestic'],
                    ['code' => 'TRAVEL_INTERNATIONAL', 'name' => 'Business Travel – International'],
                ],
            ],
        ];
    }

    /**
     * CFC (Corporate Finance Controller) Activities
     */
    private function getCFCActivities(): array
    {
        return [
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_CC_BREAKDOWN', 'name' => 'Administration – Credit Card Breakdown'],
                    ['code' => 'ADMIN_TRANSFER_INPUT', 'name' => 'Administration – Transfer Input'],
                    ['code' => 'ADMIN_CC_PAYMENT', 'name' => 'Administration – Credit Card Payment'],
                    ['code' => 'ADMIN_PAYMENT_LINK', 'name' => 'Administration – Payment via Payment Link'],
                    ['code' => 'ADMIN_PAYMENT_INFO', 'name' => 'Administration – Payment Information'],
                    ['code' => 'ADMIN_BANKING', 'name' => 'Administration – Banking'],
                    ['code' => 'ADMIN_REVIEW_CHECK', 'name' => 'Administration – Review & Checking'],
                    ['code' => 'ADMIN_PAYMENT_REQUEST', 'name' => 'Administration – Payment Request'],
                    ['code' => 'ADMIN_EXPENSE_APPROVAL', 'name' => 'Administration – Expense Realization Approval'],
                    ['code' => 'ADMIN_TRANSFER_APPROVAL', 'name' => 'Administration – Transfer Approval'],
                    ['code' => 'ADMIN_FUND_REQUEST_APPROVAL', 'name' => 'Administration – Fund Request Approval'],
                    ['code' => 'ADMIN_OVERBOOKING_APPROVAL', 'name' => 'Administration – Overbooking Approval'],
                    ['code' => 'ADMIN_PETTY_CASH', 'name' => 'Administration – Petty Cash'],
                    ['code' => 'ADMIN_JURNAL_PAYMENT_UPDATE', 'name' => 'Administration – Jurnal.ID Payment Status Update'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_HANDOVER', 'name' => 'Administration – Handover'],
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
                'code' => 'E_BANKING',
                'name' => 'E-Banking',
                'sub_activities' => [
                    ['code' => 'E_BANKING', 'name' => 'E-Banking'],
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
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
                ],
            ],
            [
                'code' => 'DATA_PROCESSING',
                'name' => 'Data Processing',
                'sub_activities' => [
                    ['code' => 'DATA_PROCESSING', 'name' => 'Data Processing'],
                ],
            ],
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_CORPORATE', 'name' => 'Project – Corporate'],
                    ['code' => 'PROJECT_CREATIVE', 'name' => 'Project – Creative'],
                    ['code' => 'PROJECT_RETAIL', 'name' => 'Project – Retail'],
                    ['code' => 'PROJECT_TRAINING', 'name' => 'Project – Training'],
                    ['code' => 'PROJECT_TRAVEL', 'name' => 'Project – Travel'],
                    ['code' => 'PROJECT_WELLNESS', 'name' => 'Project – Wellness'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
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
                'code' => 'ENTERTAINMENT',
                'name' => 'Entertainment',
                'sub_activities' => [
                    ['code' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
                ],
            ],
            [
                'code' => 'PERFORMANCE_MANAGEMENT',
                'name' => 'Performance Management',
                'sub_activities' => [
                    ['code' => 'APPRAISAL', 'name' => 'Appraisal'],
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
                'code' => 'BUSINESS_TRAVEL',
                'name' => 'Business Travel',
                'sub_activities' => [
                    ['code' => 'TRAVEL_DOMESTIC', 'name' => 'Business Travel – Domestic'],
                    ['code' => 'TRAVEL_INTERNATIONAL', 'name' => 'Business Travel – International'],
                ],
            ],
        ];
    }

    /**
     * GA (General Affair) Activities
     */
    private function getGAActivities(): array
    {
        return [
            [
                'code' => 'ACTION_PLAN',
                'name' => 'Action Plan',
                'sub_activities' => [
                    ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
                ],
            ],
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_VERIFICATION', 'name' => 'Administration – Verification & Approval'],
                    ['code' => 'ADMIN_REVIEW_CHECK', 'name' => 'Administration – Review & Checking'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_SUBMISSION_REALIZATION', 'name' => 'Administration – Submission & Realization'],
                    ['code' => 'ADMIN_INVENTORY_CONTROL', 'name' => 'Administration – Inventory Control'],
                ],
            ],
            [
                'code' => 'RESEARCH',
                'name' => 'Research',
                'sub_activities' => [
                    ['code' => 'RESEARCH_SEARCHING', 'name' => 'Research – Searching'],
                ],
            ],
            [
                'code' => 'ENTERTAINMENT',
                'name' => 'Entertainment',
                'sub_activities' => [
                    ['code' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
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
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
                ],
            ],
            [
                'code' => 'DATA_PROCESSING',
                'name' => 'Data Processing',
                'sub_activities' => [
                    ['code' => 'DATA_PROCESSING', 'name' => 'Data Processing'],
                ],
            ],
            [
                'code' => 'BUSINESS_TRAVEL',
                'name' => 'Business Travel',
                'sub_activities' => [
                    ['code' => 'TRAVEL_DOMESTIC', 'name' => 'Business Travel – Domestic'],
                    ['code' => 'TRAVEL_INTERNATIONAL', 'name' => 'Business Travel – International'],
                ],
            ],
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_CORPORATE', 'name' => 'Project – Corporate'],
                    ['code' => 'PROJECT_CREATIVE', 'name' => 'Project – Creative'],
                    ['code' => 'PROJECT_RETAIL', 'name' => 'Project – Retail'],
                    ['code' => 'PROJECT_TRAINING', 'name' => 'Project – Training'],
                    ['code' => 'PROJECT_TRAVEL', 'name' => 'Project – Travel'],
                    ['code' => 'PROJECT_WELLNESS', 'name' => 'Project – Wellness'],
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
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
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
                'code' => 'HOUSEKEEPING',
                'name' => 'Housekeeping',
                'sub_activities' => [
                    ['code' => 'CLEANLINESS_TIDINESS', 'name' => 'Cleanliness & Tidiness'],
                ],
            ],
            [
                'code' => 'SUPPORT_ACTIVITIES',
                'name' => 'Support Activities',
                'sub_activities' => [
                    ['code' => 'SUPPORT_DEPARTMENT', 'name' => 'Support – Department'],
                    ['code' => 'SUPPORT_BOD', 'name' => 'Support – BOD'],
                ],
            ],
            [
                'code' => 'FOOD_BEVERAGE',
                'name' => 'Food & Beverage',
                'sub_activities' => [
                    ['code' => 'FNB_SERVICE', 'name' => 'FNB Service'],
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

    /**
     * HR (Human Resource) Activities
     */
    private function getHRActivities(): array
    {
        return [
            [
                'code' => 'ACTION_PLAN',
                'name' => 'Action Plan',
                'sub_activities' => [
                    ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
                ],
            ],
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_REQUESTS_PROCUREMENT', 'name' => 'Administration – Requests & Procurement'],
                    ['code' => 'ADMIN_PROJECT', 'name' => 'Administration – Project'],
                    ['code' => 'ADMIN_RECRUITMENT', 'name' => 'Administration – Recruitment'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
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
                    ['code' => 'INT_ADMIN_ACTIVITIES', 'name' => 'Internal Activities – Administrative Activities'],
                ],
            ],
            [
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
                ],
            ],
            [
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_PTM', 'name' => 'Meeting – PTM'],
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting – Internal'],
                    ['code' => 'MEETING_EXTERNAL', 'name' => 'Meeting – External'],
                    ['code' => 'COORDINATION_INTERNAL', 'name' => 'Coordination – Internal'],
                    ['code' => 'COORDINATION_EXTERNAL', 'name' => 'Coordination – External'],
                ],
            ],
            [
                'code' => 'PEOPLE_DEVELOPMENT',
                'name' => 'People Development',
                'sub_activities' => [
                    ['code' => 'COUNSELING', 'name' => 'Counseling'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
                    ['code' => 'SOP', 'name' => 'Standard Operating Procedure (SOP)'],
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                ],
            ],
            [
                'code' => 'RESEARCH',
                'name' => 'Research',
                'sub_activities' => [
                    ['code' => 'RESEARCH_DEPARTMENT', 'name' => 'Research – Department'],
                ],
            ],
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_INCHARGE', 'name' => 'Project – Incharge'],
                    ['code' => 'PROJECT_ADMINISTRATION', 'name' => 'Project – Administration'],
                ],
            ],
            [
                'code' => 'RECRUITMENT_PROCESS',
                'name' => 'Recruitment Process',
                'sub_activities' => [
                    ['code' => 'SCHEDULING_INTERVIEW', 'name' => 'Scheduling Interview'],
                    ['code' => 'INTERVIEW', 'name' => 'Interview'],
                    ['code' => 'SCREENING_DATA', 'name' => 'Screening Data'],
                ],
            ],
            [
                'code' => 'SUPPORT_ACTIVITIES',
                'name' => 'Support Activities',
                'sub_activities' => [
                    ['code' => 'SUPPORT_DEPARTMENT', 'name' => 'Support – Department'],
                ],
            ],
            [
                'code' => 'WELLBEING',
                'name' => 'Wellbeing',
                'sub_activities' => [
                    ['code' => 'WELLBEING_ACTIVITIES', 'name' => 'Wellbeing – Activities'],
                    ['code' => 'WELLBEING_REPORTING', 'name' => 'Wellbeing – Reporting'],
                ],
            ],
            [
                'code' => 'BUSINESS_TRAVEL',
                'name' => 'Business Travel',
                'sub_activities' => [
                    ['code' => 'TRAVEL_DOMESTIC', 'name' => 'Business Travel – Domestic'],
                    ['code' => 'TRAVEL_INTERNATIONAL', 'name' => 'Business Travel – International'],
                ],
            ],
        ];
    }

    /**
     * PD (Product Development) Activities
     */
    private function getPDActivities(): array
    {
        return [
            [
                'code' => 'FOLLOW_UP_LEAD',
                'name' => 'Follow Up Lead',
                'sub_activities' => [
                    ['code' => 'LEAD_PROPOSAL', 'name' => 'Lead Follow Up – Proposal'],
                    ['code' => 'LEAD_QUOTATION', 'name' => 'Lead Follow Up – Quotation'],
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
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_DATA_ENTRY', 'name' => 'Administration – Data Entry & Database'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_VERIFICATION', 'name' => 'Administration – Verification & Approval'],
                    ['code' => 'ADMIN_REQUESTS_PROCUREMENT', 'name' => 'Administration – Requests & Procurement'],
                    ['code' => 'ADMIN_REVIEW_CHECK', 'name' => 'Administration – Review & Checking'],
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
                'code' => 'INTERNAL_ACTIVITIES',
                'name' => 'Internal Activities',
                'sub_activities' => [
                    ['code' => 'INT_EVENTS', 'name' => 'Internal Activities – Internal Events'],
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
                'code' => 'ENTERTAINMENT',
                'name' => 'Entertainment',
                'sub_activities' => [
                    ['code' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
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
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_INCHARGE', 'name' => 'Project – Incharge'],
                ],
            ],
            [
                'code' => 'PRODUCT_DEVELOPMENT',
                'name' => 'Product Development',
                'sub_activities' => [
                    ['code' => 'PRODUCT_DEV_EVENT_INSPECTION', 'name' => 'Product Dev – Event/Market Inspection'],
                    ['code' => 'PRODUCT_DEV_SURVEY_INSPECTION', 'name' => 'Product Dev – Survey & Inspection'],
                    ['code' => 'PRODUCT_DEV_MARKET_RESEARCH', 'name' => 'Product Dev – Market Research'],
                    ['code' => 'PRODUCT_DEV_CREATIVE_CONCEPT', 'name' => 'Product Dev – Creative Concept'],
                    ['code' => 'PRODUCT_DEV_DESIGN', 'name' => 'Product Dev – Design Product'],
                    ['code' => 'PRODUCT_TESTING', 'name' => 'Product – Testing'],
                ],
            ],
            [
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                ],
            ],
            [
                'code' => 'WELLBEING',
                'name' => 'Wellbeing',
                'sub_activities' => [
                    ['code' => 'WELLBEING_ACTIVITIES', 'name' => 'Wellbeing – Activities'],
                ],
            ],
        ];
    }

    /**
     * SO (Sales Operation) Activities
     */
    private function getSOActivities(): array
    {
        return [
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
                'code' => 'LEAVE',
                'name' => 'Leave',
                'sub_activities' => [
                    ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave – Annual'],
                    ['code' => 'LEAVE_LATE_ARRIVAL', 'name' => 'Leave – Late Arrival Permission'],
                    ['code' => 'LEAVE_PERMISSION', 'name' => 'Leave – Permission'],
                    ['code' => 'LEAVE_SICK', 'name' => 'Leave – Sick'],
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
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_MEETING_VENDOR', 'name' => 'Project – Meeting dengan Vendor'],
                    ['code' => 'PROJECT_MEETING_KLIEN', 'name' => 'Project – Meeting dengan Klien'],
                    ['code' => 'PROJECT_BRIEFING_TEAM', 'name' => 'Project – Briefing Team Project'],
                    ['code' => 'PROJECT_PREPARATION', 'name' => 'Project – Preparation'],
                    ['code' => 'PROJECT_SURVEY_VENUE', 'name' => 'Project – Survey Venue'],
                    ['code' => 'PROJECT_ADMINISTRATION', 'name' => 'Project – Administration'],
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
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
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
                'code' => 'VENDOR_PARTNERSHIP',
                'name' => 'Vendor & Partnership',
                'sub_activities' => [
                    ['code' => 'VENDOR_FOLLOW_UP', 'name' => 'Vendor – Follow Up'],
                ],
            ],
            [
                'code' => 'EVALUATION',
                'name' => 'Evaluation',
                'sub_activities' => [
                    ['code' => 'EVALUATION', 'name' => 'Evaluation'],
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
                'code' => 'PRODUCT_DEVELOPMENT',
                'name' => 'Product Development',
                'sub_activities' => [
                    ['code' => 'PRODUCT_DEV_MEETING', 'name' => 'Product Dev – Meeting'],
                    ['code' => 'PRODUCT_DEV_RND', 'name' => 'Product Dev – Research & Development'],
                    ['code' => 'PRODUCT_DEV_PRESENTATION', 'name' => 'Product Dev – Presentation'],
                    ['code' => 'PRODUCT_TESTING', 'name' => 'Product – Testing'],
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
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_DATA_ENTRY', 'name' => 'Administration – Data Entry & Database'],
                    ['code' => 'ADMIN_ARCHIVING', 'name' => 'Administration – Archiving & Records Management'],
                    ['code' => 'ADMIN_VERIFICATION', 'name' => 'Administration – Verification & Approval'],
                    ['code' => 'ADMIN_REQUESTS_PROCUREMENT', 'name' => 'Administration – Requests & Procurement'],
                ],
            ],
            [
                'code' => 'BUSINESS_TRAVEL',
                'name' => 'Business Travel',
                'sub_activities' => [
                    ['code' => 'TRAVEL_DOMESTIC', 'name' => 'Business Travel – Domestic'],
                    ['code' => 'TRAVEL_INTERNATIONAL', 'name' => 'Business Travel – International'],
                ],
            ],
        ];
    }

    /**
     * SS (Strategic Sourcing) Activities
     */
    private function getSSActivities(): array
    {
        return [
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_PO_CREATION', 'name' => 'Administration – PO Creation & Revision'],
                    ['code' => 'ADMIN_PO_PROCESSING', 'name' => 'Administration – PO Processing'],
                    ['code' => 'ADMIN_RECEIVING', 'name' => 'Administration – Receiving'],
                    ['code' => 'ADMIN_DOCUMENT_PREP', 'name' => 'Administration – Form, Letter & Document Preparation'],
                    ['code' => 'ADMIN_GENERAL', 'name' => 'Administration – General'],
                ],
            ],
            [
                'code' => 'DATA_PROCESSING',
                'name' => 'Data Processing',
                'sub_activities' => [
                    ['code' => 'DATA_PROCESSING', 'name' => 'Data Processing'],
                ],
            ],
            [
                'code' => 'VENDOR_PARTNERSHIP',
                'name' => 'Vendor & Partnership',
                'sub_activities' => [
                    ['code' => 'VENDOR_SOURCING', 'name' => 'Vendor – Sourcing'],
                    ['code' => 'VENDOR_INVOICE_PAYMENT', 'name' => 'Vendor – Invoice, Payment Reconciliation & Follow Up'],
                    ['code' => 'VENDOR_COURTESY', 'name' => 'Vendor – Courtesy'],
                    ['code' => 'VENDOR_NEGOTIATION', 'name' => 'Vendor – Negotiation'],
                    ['code' => 'VENDOR_EVALUATION', 'name' => 'Vendor – Evaluation'],
                ],
            ],
            [
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting – Internal'],
                    ['code' => 'MEETING_INTERDEPARTMENTAL', 'name' => 'Meeting – Interdepartmental'],
                    ['code' => 'MEETING_VENDOR', 'name' => 'Meeting – Vendor'],
                    ['code' => 'MEETING_RAB', 'name' => 'Meeting – RAB'],
                    ['code' => 'MEETING_PTM', 'name' => 'Meeting – PTM'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
                ],
            ],
            [
                'code' => 'REPORTING',
                'name' => 'Reporting',
                'sub_activities' => [
                    ['code' => 'REPORTING_PROJECT', 'name' => 'Reporting – Project'],
                    ['code' => 'REPORTING_DEPARTMENT', 'name' => 'Reporting – Department'],
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
                'code' => 'WELLBEING',
                'name' => 'Wellbeing',
                'sub_activities' => [
                    ['code' => 'WELLBEING_ACTIVITIES', 'name' => 'Wellbeing – Activities'],
                ],
            ],
            [
                'code' => 'SUPPORT_ACTIVITIES',
                'name' => 'Support Activities',
                'sub_activities' => [
                    ['code' => 'SUPPORT_DEPARTMENT', 'name' => 'Support – Department'],
                    ['code' => 'SUPPORT_EVENT', 'name' => 'Support – Event'],
                ],
            ],
        ];
    }

    /**
     * TEP (Tour & Event Planning) Activities
     */
    private function getTEPActivities(): array
    {
        return [
            [
                'code' => 'FOLLOW_UP_LEAD',
                'name' => 'Follow Up Lead',
                'sub_activities' => [
                    ['code' => 'LEAD_GENERAL', 'name' => 'Lead Follow Up – General'],
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
                'code' => 'EXTERNAL_ACTIVITIES',
                'name' => 'External Activities',
                'sub_activities' => [
                    ['code' => 'EXT_ENGAGEMENTS', 'name' => 'External Activities – External Engagements'],
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
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_PTM', 'name' => 'Meeting – PTM'],
                    ['code' => 'MEETING_RAB', 'name' => 'Meeting – RAB'],
                    ['code' => 'MEETING_BRAINSTORMING', 'name' => 'Meeting – Brainstorming'],
                    ['code' => 'MEETING_RECONCILIATION', 'name' => 'Meeting – Reconciliation'],
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
                'code' => 'SALES_BLITZ',
                'name' => 'Sales Blitz',
                'sub_activities' => [
                    ['code' => 'SALES_BLITZ', 'name' => 'Sales Blitz'],
                ],
            ],
            [
                'code' => 'TELEMARKETING',
                'name' => 'Telemarketing',
                'sub_activities' => [
                    ['code' => 'TELEMARKETING_CALL', 'name' => 'Telemarketing – Call'],
                    ['code' => 'TELEMARKETING_WA', 'name' => 'Telemarketing – WA'],
                    ['code' => 'TELEMARKETING_EMAIL', 'name' => 'Telemarketing – Email'],
                ],
            ],
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_MEETING', 'name' => 'Project – Meeting'],
                    ['code' => 'PROJECT_INCHARGE', 'name' => 'Project – Incharge'],
                    ['code' => 'PROJECT_MONITORING', 'name' => 'Project – Monitoring'],
                    ['code' => 'PROJECT_PELAPORAN', 'name' => 'Project – Pelaporan'],
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
                'code' => 'ACTION_PLAN',
                'name' => 'Action Plan',
                'sub_activities' => [
                    ['code' => 'ACTION_PLAN_REPORTING', 'name' => 'Action Plan – Reporting'],
                    ['code' => 'ACTION_PLAN_IMPLEMENTATION', 'name' => 'Action Plan – Implementation Activities'],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'sub_activities' => [
                    ['code' => 'TRAINING_EXTERNAL', 'name' => 'Training – External'],
                    ['code' => 'TRAINING_INTERNAL', 'name' => 'Training – Internal'],
                    ['code' => 'CONDUCT_TRAINING', 'name' => 'Conduct Training'],
                ],
            ],
            [
                'code' => 'ADMINISTRATION',
                'name' => 'Administration',
                'sub_activities' => [
                    ['code' => 'ADMIN_GENERAL', 'name' => 'Administration – General'],
                ],
            ],
            [
                'code' => 'WELLBEING',
                'name' => 'Wellbeing',
                'sub_activities' => [
                    ['code' => 'WELLBEING_ACTIVITIES', 'name' => 'Wellbeing – Activities'],
                ],
            ],
        ];
    }
}
