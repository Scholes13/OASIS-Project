<?php

namespace Database\Factories\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeTaskFactory extends Factory
{
    protected $model = EmployeeTask::class;

    public function definition(): array
    {
        return [
            'business_unit_id' => BusinessUnit::factory(),
            'department_id' => Department::factory(),
            'created_by' => User::factory(),
            'activity_type_id' => ActivityType::factory(),
            'task_title' => $this->faker->sentence(),
            'task_description' => $this->faker->paragraph(),
            'task_date' => now(),
            'status' => 'planned',
            'priority' => 'medium',
        ];
    }
}
