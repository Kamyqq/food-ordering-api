<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_mail' => $this->faker->unique()->safeEmail(),
            'client_phone' => '+48 123 456 789',
            'client_address' => $this->faker->streetAddress(),
            'total_price' => $this->faker->numberBetween(2000, 150000),
            'status' => OrderStatus::PENDING,
            'stripe_payment_id' => null,
        ];
    }
}
