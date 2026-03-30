<?php

namespace App\Http\Controllers;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller {
    public function index(): JsonResponse {
        return response()->json(Role::query()->get());
    }

    public function permissions(): JsonResponse {
        return response()->json(Permission::query()->pluck('name'));
    }

    public function details(): JsonResponse {
        $roles = Role::with('permissions:name')->get()->map(fn (Role $role): array => [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->values(),
        ]);

        return response()->json($roles);
    }

    public function userRoles(string $userId): JsonResponse {
        $user = $this->resolveUser($userId);
        $roles = $user->getRoleNames();

        return response()->json($roles->values());
    }

    private function resolveUser(string $userId): User {
        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        return $user;
    }
}
