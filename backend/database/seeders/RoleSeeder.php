<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (!$tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');
            return;
        }

        // ─── Criar Roles para o tenant ───────────────

        $adminRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Administrador'],
            ['description' => 'Administrador do restaurante com acesso total.'],
        );

        $gerenteRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Gerente'],
            ['description' => 'Gerente com acesso a catalogo, pedidos e usuarios.'],
        );

        $atendenteRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Atendente'],
            ['description' => 'Atendente com acesso a pedidos e mesas.'],
        );

        // ─── Vincular Permissoes ─────────────────────

        // Administrador: todas as permissoes operacionais (sem plans/tenants/profiles)
        $adminPermissions = Permission::whereNotIn('name', [
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete',
            'profiles.view', 'profiles.create', 'profiles.edit', 'profiles.delete',
        ])->pluck('id')->toArray();
        $adminRole->permissions()->sync($adminPermissions);

        // Gerente: catalogo + pedidos + mesas + usuarios (sem roles)
        $gerentePermissions = Permission::whereIn('name', [
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'tables.view', 'tables.create', 'tables.edit', 'tables.delete',
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'users.view',
        ])->pluck('id')->toArray();
        $gerenteRole->permissions()->sync($gerentePermissions);

        // Atendente: apenas pedidos e mesas (visualizacao + criacao)
        $atendentePermissions = Permission::whereIn('name', [
            'categories.view',
            'products.view',
            'tables.view',
            'orders.view', 'orders.create', 'orders.edit',
        ])->pluck('id')->toArray();
        $atendenteRole->permissions()->sync($atendentePermissions);

        // ─── Vincular Role ao usuario gerente ────────

        $gerente = User::where('email', 'gerente@demo.com')->first();
        if ($gerente) {
            $gerente->roles()->sync([$gerenteRole->id]);
        }
    }
}