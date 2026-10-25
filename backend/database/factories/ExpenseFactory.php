<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'amount' => fake()->numberBetween(5, 200) * 1000,
            'category' => fake()->randomElement(Expense::CATEGORIES),
            'spent_on' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'note' => null,
        ];
    }
}
