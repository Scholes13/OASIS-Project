<?php

namespace Database\Factories\Modules\Activity;

use App\Models\Modules\Activity\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityTypeFactory extends Factory
{
    protected $model = ActivityType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => strtoupper($this->faker->lexify('???')),
            'color' => $this->faker->hexColor(),
            'is_active' => true,
        ];
    }
}
