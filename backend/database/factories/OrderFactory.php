<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'status' => Order::STATUS_OPEN,
            'total' => $this->faker->randomFloat(2, 10, 500),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }

    public function delivered(): static
    {
        return $this->state(['status' => Order::STATUS_DELIVERED]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => Order::STATUS_REJECTED]);
    }
}
