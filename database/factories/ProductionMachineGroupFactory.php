<?php

namespace Database\Factories;

use App\Models\ProductionMachineGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionMachineGroup>
 */
class ProductionMachineGroupFactory extends Factory
{
    protected $model = ProductionMachineGroup::class;

    public function definition(): array
    {
        return [
            'production_id' => null,
            'machine_group_id' => null,
            'machine_count' => $this->faker->numberBetween(1, 5),
        ];
    }
}
