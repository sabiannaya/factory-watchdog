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
            'recorded_at' => $this->faker->dateTimeBetween('-1 days', 'now'),
            'output_qty_normal' => $this->faker->numberBetween(0, 200),
            'output_qty_reject' => $this->faker->numberBetween(0, 50),
        ];
    }
}
