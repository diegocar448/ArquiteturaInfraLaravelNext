<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'plan_id' => Plan::factory(),
            'uuid' => (string) Str::uuid(),
            'cnpj' => fake()->unique()->numerify('##.###.###/####-##'),
            'name' => $name,
            'url' => Str::slug($name),
            'email' => fake()->unique()->companyEmail(),
            'active' => true,
        ];
    }
}
