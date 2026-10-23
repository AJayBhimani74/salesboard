<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'region' => fake()->randomElement(Order::REGIONS),
            'category' => fake()->randomElement(Order::CATEGORIES),
            'status' => 'completed',
            'amount' => fake()->numberBetween(20000, 900000),
            'ordered_at' => fake()->dateTimeBetween('-12 months'),
        ];
    }
}
