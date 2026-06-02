<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed activity types for the WNS / SM root department (Sales & Marketing) as
 * the UNION of its three sub-divisions BS + COM + CMC, deduplicated by purpose.
 *
 * Built DYNAMICALLY from the live BS_/COM_/CMC_ activity types (which must be
 * seeded first) so SM stays in sync. Merge rules (PO 2026-05-26):
 *   - Exact same-name types are merged (Action Plan, Project, Training, etc.).
 *   - Purpose-same / different-wording merges (canonicalName map):
 *       Administration  -> Administrasi
 *       Report          -> Reporting
 *       Internal Activity -> Internal Activities
 *   - 'Internal Meeting' (COM) is demoted to a sub 'Meeting – Internal' under
 *     'Meeting & Coordination'.
 *   - Kept separate (PO): Eksternal Meeting, Support Project, Brainstorming.
 *
 * Type codes are slugified from the canonical name (SM_<SLUG>); sub codes reuse
 * the source sub's base code (prefix stripped). Idempotent via updateOrCreate /
 * updateOrInsert. Safe to re-run.
 */
class WNSSmActivityTypeSeeder extends Seeder
{
    private const DEPT_CODE = 'SM';

    /** Source sub-division prefixes, iterated in this order for deterministic dedup. */
    private const SOURCE_PREFIXES = ['BS', 'COM', 'CMC'];

    /** Lowercased source type name => canonical display name to merge into. */
    private array $canonicalName = [
        'administration' => 'Administrasi',
        'report' => 'Reporting',
        'internal activity' => 'Internal Activities',
    ];

    /** Lowercased source type name => demote its content into a sub of another type. */
    private array $demoteToSub = [
        'internal meeting' => ['parent' => 'Meeting & Coordination', 'sub_name' => 'Meeting – Internal'],
    ];

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

        $sm = Department::where('business_unit_id', $wns->id)
            ->where('code', self::DEPT_CODE)
            ->first();
        if (! $sm) {
            $this->command->error('Department WNS/SM not found. Run WNSSalesMarketingSeeder first.');

            return;
        }

        // Accumulate the union, keyed by canonical normalized name.
        $union = [];        // canonNorm => ['display' => str, 'subs' => [subNorm => ['code'=>, 'name'=>]]]
        $demoted = [];      // ['parent' => str, 'sub_name' => str]

        foreach (self::SOURCE_PREFIXES as $prefix) {
            $types = ActivityType::where('code', 'like', $prefix.'\_%')
                ->orderBy('sort_order')
                ->get();

            foreach ($types as $type) {
                $normName = mb_strtolower(trim($type->name));

                if (isset($this->demoteToSub[$normName])) {
                    $demoted[] = $this->demoteToSub[$normName];

                    continue;
                }

                $display = $this->canonicalName[$normName] ?? $type->name;
                $canonNorm = mb_strtolower(trim($display));

                if (! isset($union[$canonNorm])) {
                    $union[$canonNorm] = ['display' => $display, 'subs' => []];
                }

                foreach ($type->subActivities()->orderBy('sort_order')->get() as $sub) {
                    $subNorm = mb_strtolower(trim($sub->name));
                    if (! isset($union[$canonNorm]['subs'][$subNorm])) {
                        $union[$canonNorm]['subs'][$subNorm] = [
                            'code' => preg_replace('/^(BS|COM|CMC)_/', '', $sub->code),
                            'name' => $sub->name,
                        ];
                    }
                }
            }
        }

        $this->persistUnion($sm, $union);
        $this->persistDemoted($demoted);

        $this->command->info(
            'WNS/SM activity types seeded: '.count($union).' types (union of BS+COM+CMC, deduped) + '
            .count($demoted).' demoted sub(s).'
        );
    }

    /**
     * Create each canonical SM activity type + its deduped subs, linked to SM.
     *
     * @param  array<string, array{display: string, subs: array<string, array{code: string, name: string}>}>  $union
     */
    private function persistUnion(Department $sm, array $union): void
    {
        $sortOrder = 0;
        $colorIndex = 0;

        foreach ($union as $entry) {
            $sortOrder++;
            $typeCode = self::DEPT_CODE.'_'.$this->slug($entry['display']);

            $type = ActivityType::updateOrCreate(
                ['code' => $typeCode],
                [
                    'name' => $entry['display'],
                    'color' => $this->colors[$colorIndex % count($this->colors)],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
            $colorIndex++;

            DB::table('department_activity_types')->updateOrInsert(
                ['department_id' => $sm->id, 'activity_type_id' => $type->id],
                [
                    'is_default' => $sortOrder === 1,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $subOrder = 0;
            foreach ($entry['subs'] as $sub) {
                $subOrder++;
                SubActivity::updateOrCreate(
                    [
                        'activity_type_id' => $type->id,
                        'code' => self::DEPT_CODE.'_'.$sub['code'],
                    ],
                    [
                        'name' => $sub['name'],
                        'is_active' => true,
                        'sort_order' => $subOrder,
                    ]
                );
            }
        }
    }

    /**
     * Add demoted entries (e.g. Internal Meeting) as subs under their parent type.
     *
     * @param  array<int, array{parent: string, sub_name: string}>  $demoted
     */
    private function persistDemoted(array $demoted): void
    {
        foreach ($demoted as $entry) {
            $parentCode = self::DEPT_CODE.'_'.$this->slug($entry['parent']);
            $parent = ActivityType::where('code', $parentCode)->first();

            if (! $parent) {
                $this->command->warn("Parent type {$parentCode} not found; cannot demote '{$entry['sub_name']}'.");

                continue;
            }

            $nextOrder = (int) $parent->subActivities()->max('sort_order') + 1;

            SubActivity::updateOrCreate(
                [
                    'activity_type_id' => $parent->id,
                    'code' => self::DEPT_CODE.'_'.$this->slug($entry['sub_name']),
                ],
                [
                    'name' => $entry['sub_name'],
                    'is_active' => true,
                    'sort_order' => $nextOrder,
                ]
            );
        }
    }

    /**
     * Slug a display name into an uppercase underscore code segment.
     * "Meeting & Coordination" -> "MEETING_COORDINATION".
     */
    private function slug(string $name): string
    {
        return Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->value();
    }
}
