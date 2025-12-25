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
            'name' => $this->faker->company.' Glue Spreader',
            'model' => $this->faker->bothify('GS-###'),
            // measurements using English attribute names
            'glue_kg' => $this->faker->randomFloat(2, 10, 500),
            'hardener_kg' => $this->faker->randomFloat(2, 0, 100),
            'powder_kg' => $this->faker->randomFloat(2, 0, 200),
            'colorant_kg' => $this->faker->randomFloat(2, 0, 50),
            'anti_termite_kg' => $this->faker->randomFloat(2, 0, 20),
            'viscosity' => $this->faker->randomElement(['low', 'medium', 'high']),
            'washes_per_day' => $this->faker->numberBetween(0, 5),
            'glue_loss_kg' => $this->faker->randomFloat(2, 0, 20),
            'notes' => $this->faker->optional()->sentence(),
            // operator/daily data (English keys)
            'glue_kg' => $this->faker->randomFloat(2, 0, 500),
            'hardener_kg' => $this->faker->randomFloat(2, 0, 100),
            'powder_kg' => $this->faker->randomFloat(2, 0, 200),
            'colorant_kg' => $this->faker->randomFloat(2, 0, 50),
            'anti_termite_kg' => $this->faker->randomFloat(2, 0, 20),
            'viscosity' => $this->faker->randomElement(['low', 'medium', 'high']),
            'washes_per_day' => $this->faker->numberBetween(0, 10),
            'glue_loss_kg' => $this->faker->randomFloat(2, 0, 50),
            'created_by' => 1,
            'modified_by' => null,
        ];
    }
}
