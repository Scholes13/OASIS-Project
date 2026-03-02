<?php

namespace Database\Seeders;

use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Department Activity Assignment Seeder
 *
 * Assigns global activity types to departments via the pivot table.
 * Each department gets a curated set of activity types relevant to their work.
 *
 * @see Requirements 3.2, 3.4 - Department assignment and default activity types
 */
class DepartmentActivityAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Assigning Activity Types to Departments...');

        // Get all activity types indexed by code
        $activityTypes = ActivityType::pluck('id', 'code')->toArray();

        if (empty($activityTypes)) {
            $this->command->error('No activity types found. Run GlobalActivityTypeSeeder first.');

            return;
        }

        // Get department assignments configuration
        $assignments = $this->getDepartmentAssignments();

        foreach ($assignments as $deptCode => $config) {
            $this->assignToDepartmentsByCode($deptCode, $config, $activityTypes);
        }

        $this->command->info('Department activity assignments completed.');
    }

    /**
     * Assign activity types to all departments with matching code
     */
    private function assignToDepartmentsByCode(string $deptCode, array $config, array $activityTypes): void
    {
        $departments = Department::where('code', $deptCode)->get();

        if ($departments->isEmpty()) {
            $this->command->warn("No departments found with code: {$deptCode}");

            return;
        }

        foreach ($departments as $department) {
            $this->assignToDepartment($department, $config, $activityTypes);
        }
    }

    /**
     * Assign activity types to a specific department
     */
    private function assignToDepartment(Department $department, array $config, array $activityTypes): void
    {
        $sortOrder = 1;
        $defaultSet = false;

        foreach ($config['activities'] as $activityCode) {
            if (! isset($activityTypes[$activityCode])) {
                $this->command->warn("Activity type not found: {$activityCode}");

                continue;
            }

            $isDefault = ! $defaultSet && ($config['default'] ?? null) === $activityCode;
            if ($isDefault) {
                $defaultSet = true;
            }

            DB::table('department_activity_types')->updateOrInsert(
                [
                    'department_id' => $department->id,
                    'activity_type_id' => $activityTypes[$activityCode],
                ],
                [
                    'is_default' => $isDefault,
                    'sort_order' => $sortOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $count = count($config['activities']);
        $this->command->info("  - {$department->code} ({$department->businessUnit->code}): {$count} activity types");
    }

    /**
     * Get department activity assignments configuration
     * Key: department code (applies to all BUs with this dept code)
     * Value: array with 'activities' (list of activity codes) and 'default' (default activity code)
     */
    private function getDepartmentAssignments(): array
    {
        // Common activities for all departments
        $commonActivities = [
            'LEAVE',
            'TRAINING',
            'REPORTING',
            'ACTION_PLAN',
            'MEETING_COORDINATION',
            'EXTERNAL_ACTIVITIES',
            'INTERNAL_ACTIVITIES',
            'WELLBEING',
            'BUSINESS_TRAVEL',
        ];

        return [
            // WNS Departments
            'ACC' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'FINANCE_BILLING',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],
            'ACS' => [
                'default' => 'PROJECT',
                'activities' => array_merge($commonActivities, [
                    'PROJECT',
                    'FOLLOW_UP_LEAD',
                    'INTERNAL_DESIGN',
                    'MARKETING',
                    'ADMINISTRATION',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],
            'BAS' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'IT_MANAGEMENT',
                    'PROCUREMENT',
                    'WEBSITE',
                    'DATA_PROCESSING',
                    'SUPPORT_ACTIVITIES',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                    'PROJECT',
                    'QMS_ISO',
                ]),
            ],
            'BID' => [
                'default' => 'RESEARCH',
                'activities' => array_merge($commonActivities, [
                    'RESEARCH',
                    'ADMINISTRATION',
                    'DATA_PROCESSING',
                    'PROJECT',
                    'ENTERTAINMENT',
                ]),
            ],
            'CFC' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'E_BANKING',
                    'FINANCE_BILLING',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],
            'GA' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'PROCUREMENT',
                    'SUPPORT_ACTIVITIES',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],
            'HR' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                    'DATA_PROCESSING',
                    'SUPPORT_ACTIVITIES',
                ]),
            ],
            'PD' => [
                'default' => 'PROJECT',
                'activities' => array_merge($commonActivities, [
                    'PROJECT',
                    'RESEARCH',
                    'ADMINISTRATION',
                    'MARKETING',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],
            'SO' => [
                'default' => 'PROJECT',
                'activities' => array_merge($commonActivities, [
                    'PROJECT',
                    'FOLLOW_UP_LEAD',
                    'MARKETING',
                    'ADMINISTRATION',
                    'VENDOR_PARTNERSHIP',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],
            'SS' => [
                'default' => 'PROCUREMENT',
                'activities' => array_merge($commonActivities, [
                    'PROCUREMENT',
                    'VENDOR_PARTNERSHIP',
                    'ADMINISTRATION',
                    'RESEARCH',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],
            'TEP' => [
                'default' => 'PROJECT',
                'activities' => array_merge($commonActivities, [
                    'PROJECT',
                    'FOLLOW_UP_LEAD',
                    'RESERVATION_TICKETING',
                    'VISA_TRAVEL_DOCUMENTS',
                    'VENDOR_PARTNERSHIP',
                    'ADMINISTRATION',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                ]),
            ],

            // MRP Departments
            'CT' => [
                'default' => 'RESERVATION_TICKETING',
                'activities' => array_merge($commonActivities, [
                    'RESERVATION_TICKETING',
                    'VISA_TRAVEL_DOCUMENTS',
                    'FINANCE_BILLING',
                    'VENDOR_PARTNERSHIP',
                    'ADMINISTRATION',
                ]),
            ],

            // Generic departments (used across multiple BUs)
            'FIN' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'FINANCE_BILLING',
                    'E_BANKING',
                    'DATA_PROCESSING',
                ]),
            ],
            'IT' => [
                'default' => 'IT_MANAGEMENT',
                'activities' => array_merge($commonActivities, [
                    'IT_MANAGEMENT',
                    'WEBSITE',
                    'ADMINISTRATION',
                    'SUPPORT_ACTIVITIES',
                ]),
            ],
            'PROC' => [
                'default' => 'PROCUREMENT',
                'activities' => array_merge($commonActivities, [
                    'PROCUREMENT',
                    'VENDOR_PARTNERSHIP',
                    'ADMINISTRATION',
                ]),
            ],
            'OPS' => [
                'default' => 'PROJECT',
                'activities' => array_merge($commonActivities, [
                    'PROJECT',
                    'ADMINISTRATION',
                    'DATA_PROCESSING',
                    'SUPPORT_ACTIVITIES',
                ]),
            ],
            'SALES' => [
                'default' => 'FOLLOW_UP_LEAD',
                'activities' => array_merge($commonActivities, [
                    'FOLLOW_UP_LEAD',
                    'PROJECT',
                    'MARKETING',
                    'VENDOR_PARTNERSHIP',
                    'ADMINISTRATION',
                    'ENTERTAINMENT',
                ]),
            ],
            'ENG' => [
                'default' => 'PROJECT',
                'activities' => array_merge($commonActivities, [
                    'PROJECT',
                    'RESEARCH',
                    'ADMINISTRATION',
                    'DATA_PROCESSING',
                ]),
            ],

            // WG Executive departments
            'CEO' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                    'ENTERTAINMENT',
                ]),
            ],
            'MD' => [
                'default' => 'ADMINISTRATION',
                'activities' => array_merge($commonActivities, [
                    'ADMINISTRATION',
                    'PERFORMANCE_MANAGEMENT',
                    'PEOPLE_DEVELOPMENT',
                    'ENTERTAINMENT',
                ]),
            ],
            'SYSADMIN' => [
                'default' => 'IT_MANAGEMENT',
                'activities' => array_merge($commonActivities, [
                    'IT_MANAGEMENT',
                    'WEBSITE',
                    'ADMINISTRATION',
                    'SUPPORT_ACTIVITIES',
                ]),
            ],
        ];
    }
}
