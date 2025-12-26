<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(1),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the role is Super.
     */
    public function super(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super',
            'slug' => Role::SUPER,
            'description' => 'Super user with full access.',
        ]);
    }

    /**
     * Indicate that the role is Staff.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Staff',
            'slug' => Role::STAFF,
            'description' => 'Staff user with limited access.',
        ]);
    }
}
