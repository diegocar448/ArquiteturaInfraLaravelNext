<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            'plans' => 'Planos',
            'detail_plans' => 'Detalhes de planos',
            'tenants' => 'Tenants',
            'categories' => 'Categorias',
            'products' => 'Produtos',
            'tables' => 'Mesas',
            'orders' => 'Pedidos',
            'users' => 'Usuarios',
            'roles' => 'Papeis',
            'profiles' => 'Perfis',
        ];

        $actions = [
            'view' => 'Visualizar',
            'create' => 'Criar',
            'edit' => 'Atualizar',
            'delete' => 'Remover',
        ];

        foreach ($resources as $resource => $resourceLabel) {
            foreach ($actions as $action => $actionLabel) {
                Permission::firstOrCreate(
                    ['name' => "{$resource}.{$action}"],
                    ['description' => "{$actionLabel} {$resourceLabel}"],
                );
            }
        }
    }
}
