<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word().' '.$this->faker->randomNumber(3),
            'thickness' => $this->faker->randomElement(['1mm', '2mm', '3mm', '5mm']),
            'ply' => (string) $this->faker->randomElement([1, 2, 3]),
            'glue_type' => $this->faker->randomElement(['PVA', 'PU', 'EVA', 'None']),
            'qty' => $this->faker->numberBetween(0, 1000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
