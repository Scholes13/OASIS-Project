<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed activity types + sub-activities for the new WNS / COM department
 * (Commercial Division), part of WNS Restructure 2026.
 *
 * Follows the prefix-per-department pattern used by the live production data
 * (e.g. ACC_LEAVE, ACS_PROJECT): each activity type code is prefixed with the
 * department code (COM_) and linked to the department via the
 * department_activity_types pivot. Sub-activity codes are prefixed too.
 *
 * The PO-supplied COM list is flat (no nested sub-activities), so each
 * activity type gets a single same-named sub-activity — mirroring how
 * single-purpose types elsewhere (Entertainment, Report, Wellbeing) carry one
 * sub-activity.
 *
 * Idempotent: updateOrCreate on activity type code + sub code; updateOrInsert
 * on the pivot. Safe to re-run.
 */
class WNSComActivityTypeSeeder extends Seeder
{
    private const DEPT_CODE = 'COM';

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
            $this->command->error('Department WNS/COM not found. Run WNSSalesMarketingSeeder first.');

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
        $this->command->info("WNS/COM activity types seeded: {$count} types + sub-activities.");
    }

    /**
     * COM activity type definitions (codes without the COM_ prefix — the
     * prefix is applied in run()). Mirrors the PO-supplied COM activity list.
     * The list is flat, so each type carries one same-named sub-activity.
     *
     * @return array<int, array{code: string, name: string, sub_activities: array<int, array{code: string, name: string}>}>
     */
    private function activities(): array
    {
        return [
            ['code' => 'COSTING', 'name' => 'Costing', 'sub_activities' => [
                ['code' => 'COSTING', 'name' => 'Costing'],
            ]],
            ['code' => 'PEMBUATAN_QUOTATION', 'name' => 'Pembuatan Quotation', 'sub_activities' => [
                ['code' => 'PEMBUATAN_QUOTATION', 'name' => 'Pembuatan Quotation'],
            ]],
            ['code' => 'PEMBUATAN_PROPOSAL', 'name' => 'Pembuatan Proposal', 'sub_activities' => [
                ['code' => 'PEMBUATAN_PROPOSAL', 'name' => 'Pembuatan Proposal'],
            ]],
            ['code' => 'PEMBUATAN_DESIGN', 'name' => 'Pembuatan Design', 'sub_activities' => [
                ['code' => 'PEMBUATAN_DESIGN', 'name' => 'Pembuatan Design'],
            ]],
            ['code' => 'BRAINSTORMING', 'name' => 'Brainstorming', 'sub_activities' => [
                ['code' => 'BRAINSTORMING', 'name' => 'Brainstorming'],
            ]],
            ['code' => 'INTERNAL_ACTIVITY', 'name' => 'Internal Activity', 'sub_activities' => [
                ['code' => 'INTERNAL_ACTIVITY', 'name' => 'Internal Activity'],
            ]],
            ['code' => 'INTERNAL_MEETING', 'name' => 'Internal Meeting', 'sub_activities' => [
                ['code' => 'INTERNAL_MEETING', 'name' => 'Internal Meeting'],
            ]],
            ['code' => 'EKSTERNAL_MEETING', 'name' => 'Eksternal Meeting', 'sub_activities' => [
                ['code' => 'EKSTERNAL_MEETING', 'name' => 'Eksternal Meeting'],
            ]],
            ['code' => 'FOLLOW_UP_VENDOR', 'name' => 'Follow Up Vendor', 'sub_activities' => [
                ['code' => 'FOLLOW_UP_VENDOR', 'name' => 'Follow Up Vendor'],
            ]],
            ['code' => 'ACTION_PLAN', 'name' => 'Action Plan', 'sub_activities' => [
                ['code' => 'ACTION_PLAN', 'name' => 'Action Plan'],
            ]],
            ['code' => 'KPI', 'name' => 'KPI', 'sub_activities' => [
                ['code' => 'KPI', 'name' => 'KPI'],
            ]],
            ['code' => 'RECAP_INQUIRY', 'name' => 'Recap Inquiry', 'sub_activities' => [
                ['code' => 'RECAP_INQUIRY', 'name' => 'Recap Inquiry'],
            ]],
            ['code' => 'EVENT', 'name' => 'Event', 'sub_activities' => [
                ['code' => 'EVENT', 'name' => 'Event'],
            ]],
        ];
    }
}
