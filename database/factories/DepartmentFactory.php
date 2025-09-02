<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\BusinessUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'business_unit_id' => BusinessUnit::factory(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'name' => $this->faker->company . ' Department',
            'is_active' => true,
        ];
    }
}