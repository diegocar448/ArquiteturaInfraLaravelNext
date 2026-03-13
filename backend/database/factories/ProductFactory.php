<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => ucfirst($title),
            'url' => Str::slug($title),
            'flag' => fake()->randomElement(['active', 'inactive', 'featured']),
            'price' => fake()->randomFloat(2, 5, 99.99),
            'description' => fake()->sentence(),
        ];
    }
}
