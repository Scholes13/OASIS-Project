<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed activity types + sub-activities for the new WNS / BS department
 * (Business Solutions Division), part of WNS Restructure 2026.
 *
 * Per PO: BS mirrors the TEP department catalog, with these additions:
 *   - Telemarketing  -> add sub "Telemarketing – Meeting"
 *   - Meeting & Coordination -> add sub "Meeting – Presentasi"
 *   - new type "Networking Activities"
 *   - new type "Relationship Building"
 *
 * The clone is DYNAMIC: it reads the live TEP_* activity types + their
 * sub-activities and replicates them under the BS_ prefix, copying name,
 * color, and sort_order verbatim. This keeps BS in sync with whatever TEP
 * actually has at seed time, rather than hardcoding a snapshot.
 *
 * Idempotent: updateOrCreate on codes; updateOrInsert on the pivot. Safe to
 * re-run. En-dash (–) used for added sub names to match the TEP style.
 */
class WNSBsActivityTypeSeeder extends Seeder
{
    private const DEPT_CODE = 'BS';

    private const SOURCE_PREFIX = 'TEP_';

    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();
        if (! $wns) {
            $this->command->error('Business Unit WNS not found. Run BusinessUnitSeeder first.');

            return;
        }

        $bs = Department::where('business_unit_id', $wns->id)
            ->where('code', self::DEPT_CODE)
            ->first();
        if (! $bs) {
            $this->command->error('Department WNS/BS not found. Run WNSSalesMarketingSeeder first.');

            return;
        }

        $cloned = $this->cloneFromTep($bs);

        // PO additions.
        $this->addSubActivity($bs, 'BS_TELEMARKETING', 'TELEMARKETING_MEETING', 'Telemarketing – Meeting');
        $this->addSubActivity($bs, 'BS_MEETING_COORDINATION', 'MEETING_PRESENTASI', 'Meeting – Presentasi');
        $this->addType($bs, 'NETWORKING_ACTIVITIES', 'Networking Activities', '#0ea5e9', 50);
        $this->addType($bs, 'RELATIONSHIP_BUILDING', 'Relationship Building', '#06b6d4', 51);

        $this->command->info("WNS/BS activity types seeded: {$cloned} cloned from TEP + 2 new types + 2 added subs.");
    }

    /**
     * Clone every TEP_* activity type (and its sub-activities) into BS_*.
     *
     * @return int number of activity types cloned
     */
    private function cloneFromTep(Department $bs): int
    {
        $tepTypes = ActivityType::where('code', 'like', 'TEP\_%')
            ->orderBy('sort_order')
            ->get();

        foreach ($tepTypes as $tep) {
            $baseCode = preg_replace('/^TEP_/', '', $tep->code);

            $bsType = ActivityType::updateOrCreate(
                ['code' => self::DEPT_CODE.'_'.$baseCode],
                [
                    'name' => $tep->name,
                    'color' => $tep->color,
                    'is_active' => true,
                    'sort_order' => $tep->sort_order,
                ]
            );

            $this->linkToDept($bs, $bsType->id, $tep->sort_order);

            foreach ($tep->subActivities()->orderBy('sort_order')->get() as $sub) {
                $subBase = preg_replace('/^TEP_/', '', $sub->code);

                SubActivity::updateOrCreate(
                    [
                        'activity_type_id' => $bsType->id,
                        'code' => self::DEPT_CODE.'_'.$subBase,
                    ],
                    [
                        'name' => $sub->name,
                        'is_active' => true,
                        'sort_order' => $sub->sort_order,
                    ]
                );
            }
        }

        return $tepTypes->count();
    }

    /**
     * Add one sub-activity to an existing BS activity type (by code).
     * Sort order is placed after existing subs.
     */
    private function addSubActivity(Department $bs, string $typeCode, string $subBaseCode, string $subName): void
    {
        $type = ActivityType::where('code', $typeCode)->first();
        if (! $type) {
            $this->command->warn("Activity type {$typeCode} not found; cannot add sub {$subName}.");

            return;
        }

        $nextOrder = (int) $type->subActivities()->max('sort_order') + 1;

        SubActivity::updateOrCreate(
            [
                'activity_type_id' => $type->id,
                'code' => self::DEPT_CODE.'_'.$subBaseCode,
            ],
            [
                'name' => $subName,
                'is_active' => true,
                'sort_order' => $nextOrder,
            ]
        );
    }

    /**
     * Create a new standalone BS activity type with a single same-named sub.
     */
    private function addType(Department $bs, string $baseCode, string $name, string $color, int $sortOrder): void
    {
        $type = ActivityType::updateOrCreate(
            ['code' => self::DEPT_CODE.'_'.$baseCode],
            [
                'name' => $name,
                'color' => $color,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]
        );

        $this->linkToDept($bs, $type->id, $sortOrder);

        SubActivity::updateOrCreate(
            [
                'activity_type_id' => $type->id,
                'code' => self::DEPT_CODE.'_'.$baseCode,
            ],
            [
                'name' => $name,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    /**
     * Link an activity type to the BS department via the pivot.
     */
    private function linkToDept(Department $bs, int $activityTypeId, int $sortOrder): void
    {
        DB::table('department_activity_types')->updateOrInsert(
            [
                'department_id' => $bs->id,
                'activity_type_id' => $activityTypeId,
            ],
            [
                'is_default' => $sortOrder === 1,
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
