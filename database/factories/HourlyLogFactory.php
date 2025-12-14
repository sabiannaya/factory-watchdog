<?php

namespace Database\Factories;

use App\Models\HourlyLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HourlyLog>
 */
class HourlyLogFactory extends Factory
{
    protected $model = HourlyLog::class;

    public function definition(): array
    {
        return [
            'production_machine_group_id' => null,
            'machine_index' => $this->faker->numberBetween(1, 3),
            'recorded_at' => $this->faker->dateTimeBetween('-1 days', 'now'),
            'output_value' => $this->faker->numberBetween(0, 200),
            'target_value' => $this->faker->optional()->numberBetween(10, 300),
        ];
    }
}
