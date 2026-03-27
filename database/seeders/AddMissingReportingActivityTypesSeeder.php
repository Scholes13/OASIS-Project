<?php

namespace Database\Seeders;

use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Add Missing Reporting Activity Types Seeder
 *
 * Creates "Reporting" activity type for 3 departments that are missing it:
 * - SO (Sales Operation) - Dept #11 at WNS
 * - HR (Human Resource) - Dept #7 at WNS
 * - TEP (Tour & Event Planning) - Dept #9 at WNS
 *
 * Pattern follows existing Reporting activity types (e.g., ACC_REPORTING, CFC_REPORTING).
 * Each gets: 1 ActivityType + 1 SubActivity + 1 department_activity_types pivot + 1 department_sub_activities pivot
 *
 * Run: php artisan db:seed --class=AddMissingReportingActivityTypesSeeder
 */
class AddMissingReportingActivityTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Adding missing Reporting activity types for SO, HR, TEP...');

        $departments = $this->getMissingReportingConfig();

        DB::beginTransaction();

        try {
            foreach ($departments as $config) {
                $this->createReportingForDepartment($config);
            }

            DB::commit();
            $this->command->info('✅ Successfully added Reporting activity types for SO, HR, TEP.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Failed: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Configuration for the 3 missing departments
     */
    private function getMissingReportingConfig(): array
    {
        return [
            [
                'dept_code' => 'SO',
                'dept_name' => 'Sales Operation',
                'at_code' => 'SO_REPORTING',
                'at_name' => 'Reporting',
                'at_color' => '#6366f1', // Same purple tone used by other Reporting ATs
                'sub_code' => 'SO_REPORTING',
                'sub_name' => 'Reporting',
                'business_unit_id' => 2, // WNS
            ],
            [
                'dept_code' => 'HR',
                'dept_name' => 'Human Resource',
                'at_code' => 'HR_REPORTING',
                'at_name' => 'Reporting',
                'at_color' => '#6366f1',
                'sub_code' => 'HR_REPORTING',
                'sub_name' => 'Reporting',
                'business_unit_id' => 2, // WNS
            ],
            [
                'dept_code' => 'TEP',
                'dept_name' => 'Tour & Event Planning',
                'at_code' => 'TEP_REPORTING',
                'at_name' => 'Reporting',
                'at_color' => '#6366f1',
                'sub_code' => 'TEP_REPORTING',
                'sub_name' => 'Reporting',
                'business_unit_id' => 2, // WNS
            ],
        ];
    }

    /**
     * Create Reporting activity type, sub-activity, and pivot assignments
     */
    private function createReportingForDepartment(array $config): void
    {
        // 1. Create or get the Activity Type (master)
        $activityType = ActivityType::firstOrCreate(
            ['code' => $config['at_code']],
            [
                'name' => $config['at_name'],
                'color' => $config['at_color'],
                'is_active' => true,
                'sort_order' => 99, // Will be at the end of global list
            ]
        );

        $this->command->info("  Activity Type: [{$activityType->code}] {$activityType->name} (ID: {$activityType->id})");

        // 2. Create or get the Sub-Activity
        $subActivity = SubActivity::firstOrCreate(
            [
                'activity_type_id' => $activityType->id,
                'code' => $config['sub_code'],
            ],
            [
                'name' => $config['sub_name'],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $this->command->info("  Sub-Activity: [{$subActivity->code}] {$subActivity->name} (ID: {$subActivity->id})");

        // 3. Find the department in WNS (business_unit_id = 2)
        $department = DB::table('departments')
            ->where('code', $config['dept_code'])
            ->where('business_unit_id', $config['business_unit_id'])
            ->first();

        if (! $department) {
            $this->command->warn("  ⚠ Department [{$config['dept_code']}] not found in BU #{$config['business_unit_id']}. Skipping pivot assignments.");

            return;
        }

        // 4. Get current max sort_order for this department
        $maxSortOrder = DB::table('department_activity_types')
            ->where('department_id', $department->id)
            ->max('sort_order') ?? 0;

        // 5. Assign Activity Type to Department (department_activity_types pivot)
        DB::table('department_activity_types')->updateOrInsert(
            [
                'department_id' => $department->id,
                'activity_type_id' => $activityType->id,
            ],
            [
                'is_default' => false,
                'sort_order' => $maxSortOrder + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info("  Pivot department_activity_types: dept #{$department->id} -> AT #{$activityType->id} (sort: ".($maxSortOrder + 1).')');

        // 6. Assign Sub-Activity to Department (department_sub_activities pivot)
        $maxSubSortOrder = DB::table('department_sub_activities')
            ->where('department_id', $department->id)
            ->max('sort_order') ?? 0;

        DB::table('department_sub_activities')->updateOrInsert(
            [
                'department_id' => $department->id,
                'sub_activity_id' => $subActivity->id,
            ],
            [
                'is_default' => false,
                'sort_order' => $maxSubSortOrder + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info("  Pivot department_sub_activities: dept #{$department->id} -> Sub #{$subActivity->id} (sort: ".($maxSubSortOrder + 1).')');
    }
}
