<?php

namespace Database\Factories;

use App\Models\DailyTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyTarget>
 */
class DailyTargetFactory extends Factory
{
    protected $model = DailyTarget::class;

    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'target_value' => 0,
            'actual_value' => 0,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
