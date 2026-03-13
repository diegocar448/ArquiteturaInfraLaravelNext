<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Criar Perfis ────────────────────────────
        $admin = Profile::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Acesso total ao sistema.'],
        );

        $gerente = Profile::firstOrCreate(
            ['name' => 'Gerente'],
            ['description' => 'Gerenciamento do restaurante sem acesso a planos e tenants.'],
        );

        $atendente = Profile::firstOrCreate(
            ['name' => 'Atendente'],
            ['description' => 'Acesso limitado a pedidos e mesas.'],
        );

        // ─── Vincular Permissoes aos Perfis ──────────

        // Admin: TODAS as permissoes
        $allPermissions = Permission::pluck('id')->toArray();
        $admin->permissions()->sync($allPermissions);

        // Gerente: tudo exceto plans.*, tenants.*, profiles.*
        $gerentePermissions = Permission::whereNotIn('name', [
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete',
            'profiles.view', 'profiles.create', 'profiles.edit', 'profiles.delete',
        ])->pluck('id')->toArray();
        $gerente->permissions()->sync($gerentePermissions);

        // Atendente: apenas visualizar catalogo + gerenciar pedidos e mesas
        $atendentePermissions = Permission::whereIn('name', [
            'categories.view',
            'products.view',
            'tables.view',
            'orders.view', 'orders.create', 'orders.edit',
        ])->pluck('id')->toArray();
        $atendente->permissions()->sync($atendentePermissions);

        // ─── Vincular Perfis aos Planos ──────────────

        $basico = Plan::where('name', 'Basico')->first();
        $profissional = Plan::where('name', 'Profissional')->first();
        $enterprise = Plan::where('name', 'Enterprise')->first();

        // Basico: apenas Admin (dono do restaurante faz tudo)
        if ($basico) {
            $basico->profiles()->sync([$admin->id]);
        }

        // Profissional: Admin + Gerente
        if ($profissional) {
            $profissional->profiles()->sync([$admin->id, $gerente->id]);
        }

        // Enterprise: Admin + Gerente + Atendente
        if ($enterprise) {
            $enterprise->profiles()->sync([$admin->id, $gerente->id, $atendente->id]);
        }
    }
}
