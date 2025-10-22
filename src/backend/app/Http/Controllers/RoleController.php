<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller {
    public function index(): JsonResponse {
        return response()->json(Role::query()->get());
    }

    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'name' => 'required|string',
            'guard_name' => 'sometimes|string|in:api,agent',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string',
        ]);

        $role = Role::firstOrCreate([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'api',
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role, 201);
    }

    public function update(string $roleIdOrName, Request $request): JsonResponse {
        $role = $this->resolveRole($roleIdOrName);
        $data = $request->validate([
            'name' => 'sometimes|string',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string',
        ]);

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }
        $role->save();

        if (array_key_exists('permissions', $data)) {
            $role->syncPermissions($data['permissions'] ?? []);
        }

        return response()->json($role);
    }

    public function destroy(string $roleIdOrName): JsonResponse {
        $role = $this->resolveRole($roleIdOrName);
        // Prevent deleting built-in roles
        if (in_array($role->name, ['owner', 'admin', 'editor', 'reader', 'field-agent'], true)) {
            return response()->json(['message' => 'Cannot delete built-in role'], 422);
        }
        $role->delete();

        return response()->json(['deleted' => true]);
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

    public function assignToUser(string $roleIdOrName, string $userId): JsonResponse {
        $role = $this->resolveRole($roleIdOrName, 'api');
        $user = $this->resolveUser($userId);
        if ($role->guard_name !== 'api') {
            return response()->json(['message' => 'Role guard mismatch'], 422);
        }
        $user->assignRole($role->name);

        return response()->json(['assigned' => true]);
    }

    public function removeFromUser(string $roleIdOrName, string $userId): JsonResponse {
        $role = $this->resolveRole($roleIdOrName, 'api');
        $user = $this->resolveUser($userId);
        if ($role->guard_name !== 'api') {
            return response()->json(['message' => 'Role guard mismatch'], 422);
        }

        // Prevent removing owner role - ensure at least one owner exists
        if ($role->name === 'owner') {
            $ownerCount = User::role('owner')->count();
            if ($ownerCount <= 1) {
                return response()->json(['message' => 'Cannot remove the last owner role. At least one owner must exist.'], 422);
            }
        }

        // Prevent users from having no roles - ensure at least one role remains
        $userRoleCount = $user->roles()->count();
        if ($userRoleCount <= 1) {
            return response()->json(['message' => 'Cannot remove the last role. Users must have at least one role.'], 422);
        }

        $user->removeRole($role->name);

        return response()->json(['removed' => true]);
    }

    public function assignToAgent(string $roleIdOrName, string $agentId): JsonResponse {
        $role = $this->resolveRole($roleIdOrName, 'agent');
        $agent = $this->resolveAgent($agentId);
        if ($role->guard_name !== 'agent') {
            return response()->json(['message' => 'Role guard mismatch'], 422);
        }
        $agent->assignRole($role->name);

        return response()->json(['assigned' => true]);
    }

    public function removeFromAgent(string $roleIdOrName, string $agentId): JsonResponse {
        $role = $this->resolveRole($roleIdOrName, 'agent');
        $agent = $this->resolveAgent($agentId);
        if ($role->guard_name !== 'agent') {
            return response()->json(['message' => 'Role guard mismatch'], 422);
        }
        $agent->removeRole($role->name);

        return response()->json(['removed' => true]);
    }

    private function resolveRole(string $idOrName, ?string $guard = null): Role {
        $query = Role::query();
        $query->where(function ($q) use ($idOrName) {
            $q->where('id', $idOrName)->orWhere('name', $idOrName);
        });
        if ($guard !== null) {
            $query->where('guard_name', $guard);
        }
        /** @var Role $role */
        $role = $query->firstOrFail();

        return $role;
    }

    private function resolveUser(string $userId): User {
        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        return $user;
    }

    private function resolveAgent(string $agentId): Agent {
        /** @var Agent $agent */
        $agent = Agent::query()->findOrFail($agentId);

        return $agent;
    }
}
