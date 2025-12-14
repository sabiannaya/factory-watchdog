<?php

namespace Database\Factories;

use App\Models\ProductionMachineGroupTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionMachineGroupTarget>
 */
class ProductionMachineGroupTargetFactory extends Factory
{
    protected $model = ProductionMachineGroupTarget::class;

    public function definition(): array
    {
        return [
            'production_machine_group_id' => null,
            'daily_target_id' => null,
        ];
    }
}
