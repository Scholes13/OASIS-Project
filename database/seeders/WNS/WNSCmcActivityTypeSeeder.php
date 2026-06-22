<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed activity types + sub-activities for the new WNS / CMC department
 * (Corporate Marketing Communication), part of WNS Restructure 2026.
 *
 * Follows the prefix-per-department pattern used by the live production data
 * (e.g. ACC_LEAVE, ACS_PROJECT): each activity type code is prefixed with the
 * department code (CMC_) and linked to the department via the
 * department_activity_types pivot. Sub-activity codes are prefixed too.
 *
 * Idempotent: updateOrCreate on activity type code + sub code; updateOrInsert
 * on the pivot. Safe to re-run.
 */
class WNSCmcActivityTypeSeeder extends Seeder
{
    private const DEPT_CODE = 'CMC';

    /** Tailwind-ish palette cycled across activity types. */
    private array $colors = [
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b',
        '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6',
        '#06b6d4', '#0ea5e9',
    ];

    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();
        if (! $wns) {
            $this->command->error('Business Unit WNS not found. Run BusinessUnitSeeder first.');

            return;
        }

        $dept = Department::where('business_unit_id', $wns->id)
            ->where('code', self::DEPT_CODE)
            ->first();
        if (! $dept) {
            $this->command->error('Department WNS/CMC not found. Run WNSSalesMarketingSeeder first.');

            return;
        }

        $colorIndex = 0;

        foreach ($this->activities() as $sortOrder => $activity) {
            $activityCode = self::DEPT_CODE.'_'.$activity['code'];

            $activityType = ActivityType::updateOrCreate(
                ['code' => $activityCode],
                [
                    'name' => $activity['name'],
                    'color' => $this->colors[$colorIndex % count($this->colors)],
                    'is_active' => true,
                    'sort_order' => $sortOrder + 1,
                ]
            );
            $colorIndex++;

            DB::table('department_activity_types')->updateOrInsert(
                [
                    'department_id' => $dept->id,
                    'activity_type_id' => $activityType->id,
                ],
                [
                    'is_default' => $sortOrder === 0,
                    'sort_order' => $sortOrder + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            foreach ($activity['sub_activities'] as $subOrder => $sub) {
                SubActivity::updateOrCreate(
                    [
                        'activity_type_id' => $activityType->id,
                        'code' => self::DEPT_CODE.'_'.$sub['code'],
                    ],
                    [
                        'name' => $sub['name'],
                        'is_active' => true,
                        'sort_order' => $subOrder + 1,
                    ]
                );
            }
        }

        $count = count($this->activities());
        $this->command->info("WNS/CMC activity types seeded: {$count} types + sub-activities.");
    }

    /**
     * CMC activity type definitions (codes without the CMC_ prefix — the
     * prefix is applied in run()). Mirrors the PO-supplied CMC activity list.
     *
     * @return array<int, array{code: string, name: string, sub_activities: array<int, array{code: string, name: string}>}>
     */
    private function activities(): array
    {
        return [
            ['code' => 'ACTION_PLAN', 'name' => 'Action Plan', 'sub_activities' => [
                ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
            ]],
            ['code' => 'ADMINISTRASI', 'name' => 'Administrasi', 'sub_activities' => [
                ['code' => 'ADMIN_APPROVAL', 'name' => 'Administrasi - Approval'],
                ['code' => 'ADMIN_CEK_REVIEW', 'name' => 'Administrasi - Cek & Review'],
                ['code' => 'ADMIN_FILING', 'name' => 'Administrasi - Filing'],
                ['code' => 'ADMIN_PENGAJUAN', 'name' => 'Administrasi - Pengajuan'],
            ]],
            ['code' => 'CONTENT', 'name' => 'Content', 'sub_activities' => [
                ['code' => 'CONTENT_FACEBOOK', 'name' => 'Content - Facebook'],
                ['code' => 'CONTENT_INSTAGRAM', 'name' => 'Content - Instagram'],
                ['code' => 'CONTENT_WEBSITE', 'name' => 'Content - Website'],
                ['code' => 'CONTENT_YOUTUBE', 'name' => 'Content - YouTube'],
            ]],
            ['code' => 'ENTERTAINMENT', 'name' => 'Entertainment', 'sub_activities' => [
                ['code' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
            ]],
            ['code' => 'EXTERNAL_ACTIVITIES', 'name' => 'External Activities', 'sub_activities' => [
                ['code' => 'EXTERNAL_ACTIVITIES', 'name' => 'External Activities'],
            ]],
            ['code' => 'INTERNAL_ACTIVITIES', 'name' => 'Internal Activities', 'sub_activities' => [
                ['code' => 'INTERNAL_ACTIVITIES', 'name' => 'Internal Activities'],
            ]],
            ['code' => 'LEAVE', 'name' => 'Leave', 'sub_activities' => [
                ['code' => 'LEAVE_ANNUAL', 'name' => 'Leave - Annual/Cuti'],
                ['code' => 'LEAVE_PERMIT', 'name' => 'Leave - Permit'],
                ['code' => 'LEAVE_SICK', 'name' => 'Leave - Sick'],
            ]],
            ['code' => 'PROJECT', 'name' => 'Project', 'sub_activities' => [
                ['code' => 'PROJECT_CORPORATE', 'name' => 'Project - Corporate'],
                ['code' => 'PROJECT_CREATIVE', 'name' => 'Project - Creative'],
                ['code' => 'PROJECT_RETAIL', 'name' => 'Project - Retail'],
                ['code' => 'PROJECT_TRAINING', 'name' => 'Project - Training'],
                ['code' => 'PROJECT_TRAVEL', 'name' => 'Project - Travel'],
                ['code' => 'PROJECT_WELLNESS', 'name' => 'Project - Wellness'],
            ]],
            ['code' => 'REPORT', 'name' => 'Report', 'sub_activities' => [
                ['code' => 'REPORT', 'name' => 'Report'],
            ]],
            ['code' => 'SOCIAL_MEDIA', 'name' => 'Social Media', 'sub_activities' => [
                ['code' => 'SOCMED_FACEBOOK', 'name' => 'Social Media - Facebook'],
                ['code' => 'SOCMED_INSTAGRAM', 'name' => 'Social Media - Instagram'],
                ['code' => 'SOCMED_WEBSITE', 'name' => 'Social Media - Website'],
                ['code' => 'SOCMED_YOUTUBE', 'name' => 'Social Media - YouTube'],
                ['code' => 'SOCMED_LINKEDIN', 'name' => 'Social Media - LinkedIn'],
            ]],
            ['code' => 'TRAINING', 'name' => 'Training', 'sub_activities' => [
                ['code' => 'TRAINING_EKSTERNAL', 'name' => 'Training - Eksternal'],
                ['code' => 'TRAINING_INTERNAL', 'name' => 'Training - Internal'],
            ]],
            ['code' => 'WELLBEING', 'name' => 'Wellbeing', 'sub_activities' => [
                ['code' => 'WELLBEING', 'name' => 'Wellbeing'],
            ]],
            ['code' => 'RIST', 'name' => 'Rist', 'sub_activities' => [
                ['code' => 'RIST_MARKERT', 'name' => 'Rist Markert'],
            ]],
            ['code' => 'SUPPORT_PROJECT', 'name' => 'Support Project', 'sub_activities' => [
                ['code' => 'SUPPORT_PROJECT_CORPORATE', 'name' => 'Project - Corporate'],
                ['code' => 'SUPPORT_PROJECT_CREATIVE', 'name' => 'Project - Creative'],
                ['code' => 'SUPPORT_PROJECT_RETAIL', 'name' => 'Project - Retail'],
                ['code' => 'SUPPORT_PROJECT_TRAINING', 'name' => 'Project - Training'],
                ['code' => 'SUPPORT_PROJECT_TRAVEL', 'name' => 'Project - Travel'],
                ['code' => 'SUPPORT_PROJECT_WELLNESS', 'name' => 'Project - Wellness'],
            ]],
            ['code' => 'ANALISA_DATA', 'name' => 'Analisa Data', 'sub_activities' => [
                ['code' => 'ANALISA_DATA', 'name' => 'Analisa Data'],
            ]],
            ['code' => 'DATA_PROCESSING', 'name' => 'Data Processing', 'sub_activities' => [
                ['code' => 'DATA_PROCESSING', 'name' => 'Data Processing'],
            ]],
            ['code' => 'FOLLOW_UP_PROJECT', 'name' => 'Follow Up Project', 'sub_activities' => [
                ['code' => 'FOLLOW_UP_PROJECT', 'name' => 'Follow Up Project'],
            ]],
        ];
    }
}
