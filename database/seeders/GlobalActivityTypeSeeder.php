<?php

namespace Database\Seeders;

use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;

/**
 * Global Activity Type Seeder
 *
 * Creates master activity types and sub activities WITHOUT department prefix.
 * These are global records that can be assigned to any department.
 *
 * Code format: LEAVE, TRAINING, MEETING (no prefix)
 * Name format: Leave, Training, Meeting (user-facing)
 *
 * @see Requirements 1.2, 2.2 - Global activity types without prefix
 */
class GlobalActivityTypeSeeder extends Seeder
{
    private array $colors = [
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b',
        '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6',
    ];

    public function run(): void
    {
        $this->command->info('Seeding Global Activity Types...');

        $activities = $this->getMasterActivities();
        $colorIndex = 0;

        foreach ($activities as $sortOrder => $activity) {
            $activityType = ActivityType::updateOrCreate(
                ['code' => $activity['code']],
                [
                    'name' => $activity['name'],
                    'color' => $this->colors[$colorIndex % count($this->colors)],
                    'is_active' => true,
                    'sort_order' => $sortOrder + 1,
                ]
            );

            // Create sub activities
            foreach ($activity['sub_activities'] as $subOrder => $sub) {
                SubActivity::updateOrCreate(
                    [
                        'activity_type_id' => $activityType->id,
                        'code' => $sub['code'],
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
        $this->command->info("Created {$count} global activity types with sub activities.");
    }

    /**
     * Master Activity Types - consolidated from all departments
     * These are the unique activity types used across the organization
     */
    private function getMasterActivities(): array
    {
        return [
            // Common activities used by most departments
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
                    ['code' => 'ADMIN_TAX', 'name' => 'Administration – Tax'],
                    ['code' => 'ADMIN_PNL', 'name' => 'Administration – PNL'],
                    ['code' => 'ADMIN_GENERAL', 'name' => 'Administration – General'],
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
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting – Internal'],
                    ['code' => 'MEETING_EXTERNAL', 'name' => 'Meeting – External'],
                    ['code' => 'MEETING_PROJECT', 'name' => 'Meeting – Project'],
                    ['code' => 'MEETING_VENDOR', 'name' => 'Meeting – Vendor'],
                    ['code' => 'MEETING_BRAINSTORMING', 'name' => 'Meeting – Brainstorming'],
                    ['code' => 'COORDINATION_INTERNAL', 'name' => 'Coordination – Internal'],
                    ['code' => 'COORDINATION_EXTERNAL', 'name' => 'Coordination – External'],
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

            // Department-specific activities
            [
                'code' => 'PROJECT',
                'name' => 'Project',
                'sub_activities' => [
                    ['code' => 'PROJECT_INCHARGE', 'name' => 'Project – Incharge'],
                    ['code' => 'PROJECT_ADMINISTRATION', 'name' => 'Project – Administration'],
                    ['code' => 'PROJECT_SURVEY_VENUE', 'name' => 'Project – Survey Venue'],
                    ['code' => 'PROJECT_BRIEFING_TEAM', 'name' => 'Project – Briefing Team Project'],
                    ['code' => 'PROJECT_DESIGN_CORPORATE', 'name' => 'Project – Design untuk Project Corporate'],
                    ['code' => 'PROJECT_DESIGN_TRAVEL', 'name' => 'Project – Design untuk Project Travel'],
                    ['code' => 'PROJECT_DESIGN_CREATIVE', 'name' => 'Project – Design untuk Project Creative'],
                    ['code' => 'PROJECT_DESIGN_WELLNESS', 'name' => 'Project – Design untuk Project Wellness'],
                    ['code' => 'PROJECT_DESIGN_TRAINING', 'name' => 'Project – Design untuk Project Training'],
                    ['code' => 'PROJECT_DESIGN_TAKSHAKA', 'name' => 'Project – Design untuk Project Takshaka'],
                    ['code' => 'PROJECT_ECO_FRIENDLY', 'name' => 'Project – Eco Friendly Product'],
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
                'code' => 'QMS_ISO',
                'name' => 'Quality Management System (ISO)',
                'sub_activities' => [
                    ['code' => 'ISO_COMPLIANCE', 'name' => 'ISO Compliance & Implementation'],
                    ['code' => 'ISO_INTERNAL_AUDIT', 'name' => 'Internal Audit Activities'],
                    ['code' => 'ISO_EXTERNAL_AUDIT', 'name' => 'External Audit Activities'],
                    ['code' => 'ISO_DOCUMENTATION', 'name' => 'ISO Documentation & Control'],
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
                'code' => 'ENTERTAINMENT',
                'name' => 'Entertainment',
                'sub_activities' => [
                    ['code' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
                ],
            ],
            [
                'code' => 'FINANCE_BILLING',
                'name' => 'Finance & Billing',
                'sub_activities' => [
                    ['code' => 'QUOTATION', 'name' => 'Quotation'],
                    ['code' => 'INVOICING', 'name' => 'Invoicing'],
                    ['code' => 'AR_MONITORING', 'name' => 'AR Monitoring'],
                    ['code' => 'RECONCILIATION', 'name' => 'Reconciliation'],
                    ['code' => 'TAX_DOCUMENT_SUPPORT', 'name' => 'Tax/Document Support'],
                    ['code' => 'PAYMENT_KLIEN', 'name' => 'Payment Klien'],
                ],
            ],
            [
                'code' => 'E_BANKING',
                'name' => 'E-Banking',
                'sub_activities' => [
                    ['code' => 'EBANKING_TRANSFER', 'name' => 'E-Banking – Transfer'],
                    ['code' => 'EBANKING_PAYMENT', 'name' => 'E-Banking – Payment'],
                    ['code' => 'EBANKING_MONITORING', 'name' => 'E-Banking – Monitoring'],
                ],
            ],

            // Travel-specific activities (MRP)
            [
                'code' => 'RESERVATION_TICKETING',
                'name' => 'Reservation & Ticketing',
                'sub_activities' => [
                    ['code' => 'FLIGHT_RESERVATION', 'name' => 'Flight Reservation'],
                    ['code' => 'HOTEL_BOOKING', 'name' => 'Hotel Booking'],
                    ['code' => 'GROUND_TRANSPORT', 'name' => 'Ground Transport'],
                    ['code' => 'TICKET_ISSUANCE', 'name' => 'Ticket Issuance'],
                    ['code' => 'REISSUE_REFUND', 'name' => 'Re-issue/Refund'],
                    ['code' => 'ITINERARY_CONFIRMATION', 'name' => 'Itinerary & Confirmation'],
                ],
            ],
            [
                'code' => 'VISA_TRAVEL_DOCUMENTS',
                'name' => 'Visa & Travel Documents',
                'sub_activities' => [
                    ['code' => 'DOKUMEN_VISA', 'name' => 'Dokumen Visa'],
                    ['code' => 'SUPPORT_LETTER', 'name' => 'Support Letter'],
                    ['code' => 'PASSPORT_VALIDITY', 'name' => 'Passport Validity'],
                    ['code' => 'TRAVEL_INSURANCE', 'name' => 'Travel Insurance'],
                ],
            ],
            [
                'code' => 'VENDOR_PARTNERSHIP',
                'name' => 'Vendor & Partnership',
                'sub_activities' => [
                    ['code' => 'VENDOR_COURTESY', 'name' => 'Vendor – Courtesy'],
                    ['code' => 'VENDOR_FOLLOW_UP', 'name' => 'Vendor – Follow Up'],
                ],
            ],
        ];
    }
}
