<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property      int                         $id
 * @property      string                      $name
 * @property      string                      $guard_name
 * @property      Carbon|null                 $created_at
 * @property      Carbon|null                 $updated_at
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, User>       $users
 */
class Role extends SpatieRole {
    /**
     * Use tenant connection for Spatie Role tables.
     *
     * @var string
     */
    protected $connection = 'tenant';
}
