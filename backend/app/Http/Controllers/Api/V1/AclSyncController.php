<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AclSyncController extends Controller
{
    /**
     * Sync permissions de um profile.
     * POST /v1/profiles/{profile}/permissions
     * Body: { "permissions": [1, 2, 3] }
     */
    public function syncProfilePermissions(Request $request, int $profile): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $profile = Profile::findOrFail($profile);
        $profile->permissions()->sync($request->permissions);

        $profile->load('permissions');

        return response()->json([
            'message' => 'Permissoes do perfil atualizadas.',
            'data' => [
                'profile_id' => $profile->id,
                'permissions' => $profile->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Sync profiles de um plan.
     * POST /v1/plans/{plan}/profiles
     * Body: { "profiles": [1, 2] }
     */
    public function syncPlanProfiles(Request $request, int $plan): JsonResponse
    {
        $request->validate([
            'profiles' => ['required', 'array'],
            'profiles.*' => ['integer', 'exists:profiles,id'],
        ]);

        $plan = Plan::findOrFail($plan);
        $plan->profiles()->sync($request->profiles);

        $plan->load('profiles');

        return response()->json([
            'message' => 'Perfis do plano atualizados.',
            'data' => [
                'plan_id' => $plan->id,
                'profiles' => $plan->profiles->pluck('name'),
            ],
        ]);
    }

    /**
     * Sync permissions de um role.
     * POST /v1/roles/{role}/permissions
     * Body: { "permissions": [1, 2, 3] }
     */
    public function syncRolePermissions(Request $request, int $role): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::findOrFail($role);
        $role->permissions()->sync($request->permissions);

        $role->load('permissions');

        return response()->json([
            'message' => 'Permissoes do papel atualizadas.',
            'data' => [
                'role_id' => $role->id,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Sync roles de um user.
     * POST /v1/users/{user}/roles
     * Body: { "roles": [1, 2] }
     */
    public function syncUserRoles(Request $request, int $user): JsonResponse
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = User::findOrFail($user);
        $user->roles()->sync($request->roles);

        $user->load('roles');

        return response()->json([
            'message' => 'Papeis do usuario atualizados.',
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Listar todas as permissions (para exibir em checkboxes no frontend).
     * GET /v1/permissions
     */
    public function listPermissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get();

        return response()->json([
            'data' => $permissions->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
            ]),
        ]);
    }
}