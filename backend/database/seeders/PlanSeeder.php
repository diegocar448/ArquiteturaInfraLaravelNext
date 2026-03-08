<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basico',
                'price' => 0,
                'description' => 'Plano gratuito para comecar. Ideal para testar a plataforma.',
            ],
            [
                'name' => 'Profissional',
                'price' => 99.90,
                'description' => 'Para restaurantes em crescimento. Recursos avancados.',
            ],
            [
                'name' => 'Enterprise',
                'price' => 299.90,
                'description' => 'Para grandes operacoes. Recursos ilimitados e suporte prioritario.',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(
                ['name' => $plan['name']],
                $plan,
            );
        }
    }
}