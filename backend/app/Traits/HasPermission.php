<?php

namespace App\Traits;

trait HasPermission
{
    /**
     * Verifica se o usuario tem uma permissao efetiva.
     *
     * Permissao efetiva = usuario tem via Role AND plano do tenant tem via Profile.
     * Super-admin tem todas as permissoes automaticamente.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super-admin tem tudo
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Verificar se o usuario tem a permissao em algum de seus roles
        $hasRolePermission = $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('permissions.name', $permissionName);
            })
            ->exists();

        if (!$hasRolePermission) {
            return false;
        }

        // Verificar se o plano do tenant inclui essa permissao em algum profile
        $tenant = $this->tenant;

        if (!$tenant || !$tenant->plan) {
            return false;
        }

        return $tenant->plan->profiles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('permissions.name', $permissionName);
            })
            ->exists();
    }

    /**
     * Verifica se o usuario tem TODAS as permissoes informadas.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica se o usuario tem PELO MENOS UMA das permissoes informadas.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna todas as permissoes efetivas do usuario (intersecao de role + plan).
     */
    public function effectivePermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Permission::pluck('name')->toArray();
        }

        // Permissoes do usuario via roles
        $rolePermissions = \App\Models\Permission::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles()->pluck('roles.id'));
        })->pluck('name')->toArray();

        if (empty($rolePermissions)) {
            return [];
        }

        // Permissoes do plano via profiles
        $tenant = $this->tenant;

        if (!$tenant || !$tenant->plan) {
            return [];
        }

        $planPermissions = \App\Models\Permission::whereHas('profiles', function ($query) use ($tenant) {
            $query->whereIn('profiles.id', $tenant->plan->profiles()->pluck('profiles.id'));
        })->pluck('name')->toArray();

        // Intersecao = permissoes que existem em ambos
        return array_values(array_intersect($rolePermissions, $planPermissions));
    }
}