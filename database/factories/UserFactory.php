<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => '09'.fake()->unique()->numerify('#########'),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'national_id' => fake()->unique()->numerify('##########'),
        ];
    }
}
