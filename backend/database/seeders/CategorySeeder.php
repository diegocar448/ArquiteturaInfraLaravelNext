<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (! $tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');

            return;
        }

        $categories = [
            ['name' => 'Pizzas', 'description' => 'Pizzas tradicionais e especiais'],
            ['name' => 'Hambúrgueres', 'description' => 'Hambúrgueres artesanais'],
            ['name' => 'Bebidas', 'description' => 'Refrigerantes, sucos e agua'],
            ['name' => 'Sobremesas', 'description' => 'Doces e sobremesas da casa'],
            ['name' => 'Combos', 'description' => 'Combinacoes com desconto'],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $data['name']],
                array_merge($data, ['tenant_id' => $tenant->id]),
            );
        }

        $this->command->info("Categorias criadas para o tenant '{$tenant->name}'.");
    }
}
