<?php

namespace Database\Factories;

use App\Models\MachineGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MachineGroup>
 */
class MachineGroupFactory extends Factory
{
    protected $model = MachineGroup::class;

    public function definition(): array
    {
        $bases = ['Assembly', 'Cutting', 'Painting', 'Packaging', 'Inspection', 'Welding', 'Press', 'Molding', 'Finishing', 'Quality'];
        $base = $this->faker->randomElement($bases);
        $suffixType = $this->faker->randomElement(['line', 'zone', 'cell']);
        $suffixNumber = $this->faker->numberBetween(1, 8);

        return [
            'name' => sprintf('%s %s %d', $base, ucfirst($suffixType), $suffixNumber),
            'description' => $this->faker->sentence(),
        ];
    }
}
