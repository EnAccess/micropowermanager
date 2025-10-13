<?php

namespace App\Services;

use App\Models\Role\RoleDefinition;
use App\Models\Role\RoleInterface;
use App\Models\Role\Roles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RolesService {
    public function __construct(private Roles $role, private RoleDefinition $definition) {}

    public function findOrCreateRoleDefinition(string $roleName): RoleDefinition {
        return $this->definition->firstOrCreate(['role_name' => $roleName]);
    }

    /**
     * @return Collection<int, Roles>
     */
    public function findRoleByDefinition(RoleDefinition $definition): Collection {
        return $this->role->with($definition)->get();
    }

    /**
     * @return Model|false
     */
    public function attachToOwner(RoleInterface $roleOwner, Roles $role) {
        return $roleOwner->roleowner()->save($role);
    }

    public function create(RoleDefinition $definition): Roles {
        $this->role->definitions()->associate($definition);

        return $this->role;
    }
}
