<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property      int                         $id
 * @property      string                      $name
 * @property      string                      $guard_name
 * @property      Carbon|null                 $created_at
 * @property      Carbon|null                 $updated_at
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, Role>       $roles
 * @property-read Collection<int, User>       $users
 */
class Permission extends SpatiePermission {
    /**
     * Use tenant connection for Spatie Permission tables.
     *
     * @var string
     */
    protected $connection = 'tenant';
}
