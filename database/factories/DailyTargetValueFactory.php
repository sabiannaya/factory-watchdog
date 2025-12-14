<?php

namespace Database\Factories;

use App\Models\ProductionMachineGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyTargetValue>
 */
class DailyTargetValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_machine_group_id' => ProductionMachineGroup::factory(),
            'date' => $this->faker->dateTime('-30 days'),
            'field_name' => $this->faker->randomElement(['qty', 'qty_normal', 'qty_reject', 'grade', 'ukuran']),
            'target_value' => $this->faker->randomNumber(3),
            'actual_value' => $this->faker->randomNumber(3),
            'keterangan' => $this->faker->optional()->text(100),
        ];
    }
}
