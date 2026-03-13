<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::where('name', 'Profissional')->first();

        if (! $plan) {
            $this->command->warn('Plano "Profissional" nao encontrado. Rode PlanSeeder primeiro.');

            return;
        }

        $tenant = Tenant::firstOrCreate(
            ['email' => 'contato@restaurantedemo.com'],
            [
                'plan_id' => $plan->id,
                'name' => 'Restaurante Demo',
                'email' => 'contato@restaurantedemo.com',
                'cnpj' => '12.345.678/0001-90',
                'active' => true,
            ],
        );

        // Criar usuario gerente vinculado ao tenant
        User::firstOrCreate(
            ['email' => 'gerente@demo.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Gerente Demo',
                'email' => 'gerente@demo.com',
                'password' => Hash::make('password'),
            ],
        );
    }
}
