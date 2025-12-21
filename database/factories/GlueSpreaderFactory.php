<?php

namespace Database\Factories;

use App\Models\GlueSpreader;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlueSpreaderFactory extends Factory
{
    protected $model = GlueSpreader::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Glue Spreader',
            'model' => $this->faker->bothify('GS-###'),
            'capacity_ml' => $this->faker->numberBetween(100, 5000),
            'speed_mpm' => $this->faker->numberBetween(1, 200),
            'status' => $this->faker->randomElement(['active', 'maintenance', 'retired']),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
            'modified_by' => null,
        ];
    }
}
