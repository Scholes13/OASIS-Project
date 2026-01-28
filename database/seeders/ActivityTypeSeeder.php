<?php

namespace Database\Seeders;

use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activityTypes = [
            [
                'code' => 'MEETING',
                'name' => 'Meeting',
                'color' => '#3b82f6',  // Blue-500
                'sort_order' => 1,
                'sub_activities' => [
                    ['code' => 'MEETING_CLIENT', 'name' => 'Meeting Client', 'sort_order' => 1],
                    ['code' => 'MEETING_RAB', 'name' => 'Meeting RAB', 'sort_order' => 2],
                    ['code' => 'MEETING_PNL', 'name' => 'Meeting PNL', 'sort_order' => 3],
                    ['code' => 'MEETING_INTERNAL', 'name' => 'Meeting Internal', 'sort_order' => 4],
                    ['code' => 'MEETING_VENDOR', 'name' => 'Meeting Vendor', 'sort_order' => 5],
                ],
            ],
            [
                'code' => 'WEBDEV',
                'name' => 'Web Development',
                'color' => '#6366f1',  // Indigo-500
                'sort_order' => 2,
                'sub_activities' => [
                    ['code' => 'FIX_BUG', 'name' => 'Fix Bug', 'sort_order' => 1],
                    ['code' => 'UPDATE_UI', 'name' => 'Update UI', 'sort_order' => 2],
                    ['code' => 'NEW_FEATURE', 'name' => 'New Feature', 'sort_order' => 3],
                    ['code' => 'CODE_REVIEW', 'name' => 'Code Review', 'sort_order' => 4],
                    ['code' => 'DEPLOYMENT', 'name' => 'Deployment', 'sort_order' => 5],
                ],
            ],
            [
                'code' => 'EVENT',
                'name' => 'Event',
                'color' => '#a855f7',  // Purple-500
                'sort_order' => 3,
                'sub_activities' => [
                    ['code' => 'EVENT_PLANNING', 'name' => 'Event Planning', 'sort_order' => 1],
                    ['code' => 'EVENT_EXECUTION', 'name' => 'Event Execution', 'sort_order' => 2],
                    ['code' => 'EVENT_FOLLOWUP', 'name' => 'Event Follow-up', 'sort_order' => 3],
                ],
            ],
            [
                'code' => 'INTERNAL',
                'name' => 'Internal Meeting',
                'color' => '#6b7280',  // Gray-500
                'sort_order' => 4,
                'sub_activities' => [
                    ['code' => 'DAILY_STANDUP', 'name' => 'Daily Standup', 'sort_order' => 1],
                    ['code' => 'WEEKLY_REVIEW', 'name' => 'Weekly Review', 'sort_order' => 2],
                    ['code' => 'MONTHLY_REPORT', 'name' => 'Monthly Report', 'sort_order' => 3],
                ],
            ],
            [
                'code' => 'ADMIN',
                'name' => 'Administrative',
                'color' => '#f59e0b',  // Amber-500
                'sort_order' => 5,
                'sub_activities' => [
                    ['code' => 'DOCUMENTATION', 'name' => 'Documentation', 'sort_order' => 1],
                    ['code' => 'EMAIL', 'name' => 'Email', 'sort_order' => 2],
                    ['code' => 'REPORT_WRITING', 'name' => 'Report Writing', 'sort_order' => 3],
                ],
            ],
            [
                'code' => 'TRAINING',
                'name' => 'Training',
                'color' => '#22c55e',  // Green-500
                'sort_order' => 6,
                'sub_activities' => [
                    ['code' => 'INTERNAL_TRAINING', 'name' => 'Internal Training', 'sort_order' => 1],
                    ['code' => 'EXTERNAL_TRAINING', 'name' => 'External Training', 'sort_order' => 2],
                    ['code' => 'SELF_LEARNING', 'name' => 'Self Learning', 'sort_order' => 3],
                ],
            ],
        ];

        foreach ($activityTypes as $typeData) {
            $subActivities = $typeData['sub_activities'];
            unset($typeData['sub_activities']);

            // Create or update activity type
            $activityType = ActivityType::updateOrCreate(
                ['code' => $typeData['code']],
                $typeData
            );

            // Create sub-activities
            foreach ($subActivities as $subData) {
                SubActivity::updateOrCreate(
                    [
                        'activity_type_id' => $activityType->id,
                        'code' => $subData['code'],
                    ],
                    $subData
                );
            }
        }

        $this->command->info('Activity types and sub-activities seeded successfully!');
    }
}
