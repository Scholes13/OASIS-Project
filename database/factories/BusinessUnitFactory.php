<?php

namespace Database\Factories;

use App\Models\Core\BusinessUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessUnitFactory extends Factory
{
    protected $model = BusinessUnit::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('??')),
            'name' => $this->faker->company,
            'numbering_config' => [],
            'is_active' => true,
        ];
    }
}
