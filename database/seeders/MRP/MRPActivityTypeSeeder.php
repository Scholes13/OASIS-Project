<?php

namespace Database\Seeders\MRP;

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
 * - Code: CT_LEAVE (with department prefix)
 * - Name: Leave (user-facing)
 *
 * New approach:
 * - Code: LEAVE (no prefix, global)
 * - Name: Leave (user-facing)
 * - Assignment via department_activity_types pivot table
 * @see \Database\Seeders\GlobalActivityTypeSeeder
 * @see \Database\Seeders\DepartmentActivityAssignmentSeeder
 */
class MRPActivityTypeSeeder extends Seeder
{
    private array $colors = [
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b',
        '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6',
    ];

    public function run(): void
    {
        $mrp = BusinessUnit::where('code', 'MRP')->first();

        if (! $mrp) {
            $this->command->error('Business Unit MRP tidak ditemukan!');

            return;
        }

        // Seed per department
        $this->seedDepartmentActivities($mrp, 'CT', $this->getCTActivities());

        $this->command->info('MRP Activity Types seeded successfully!');
    }

    /**
     * Seed activities untuk satu departemen
     */
    private function seedDepartmentActivities(BusinessUnit $mrp, string $deptCode, array $activities): void
    {
        $department = Department::where('business_unit_id', $mrp->id)
            ->where('code', $deptCode)
            ->first();

        if (! $department) {
            $this->command->warn("Department {$deptCode} tidak ditemukan, skip...");

            return;
        }

        $colorIndex = 0;

        foreach ($activities as $sortOrder => $activity) {
            // Code dengan prefix dept: CT_LEAVE, CT_TRAINING
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

            // Sub activities dengan prefix: CT_LEAVE_SICK
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
     * CT (Corporate Travel) Activities
     */
    private function getCTActivities(): array
    {
        return [
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
                'code' => 'FINANCE_BILLING',
                'name' => 'Finance & Billing',
                'sub_activities' => [
                    ['code' => 'QUOTATION', 'name' => 'Quotation'],
                    ['code' => 'INVOICING', 'name' => 'Invoicing'],
                    ['code' => 'AR_MONITORING', 'name' => 'AR Monitoring'],
                    ['code' => 'RECONCILIATION', 'name' => 'Reconciliation'],
                    ['code' => 'TAX_DOCUMENT_SUPPORT', 'name' => 'Tax/Document Support'],
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
                'code' => 'VENDOR_PARTNERSHIP',
                'name' => 'Vendor & Partnership',
                'sub_activities' => [
                    ['code' => 'VENDOR_COURTESY', 'name' => 'Vendor – Courtesy'],
                    ['code' => 'VENDOR_FOLLOW_UP', 'name' => 'Vendor – Follow Up'],
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
                'code' => 'MEETING_COORDINATION',
                'name' => 'Meeting & Coordination',
                'sub_activities' => [
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting – Internal'],
                    ['code' => 'MEETING_EXTERNAL', 'name' => 'Meeting – External'],
                    ['code' => 'COORDINATION_INTERNAL', 'name' => 'Coordination – Internal'],
                    ['code' => 'COORDINATION_EXTERNAL', 'name' => 'Coordination – External'],
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
        ];
    }
}
