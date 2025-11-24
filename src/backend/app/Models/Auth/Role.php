<?php

namespace App\Models\Auth;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole {
    /**
     * Use tenant connection for Spatie Role tables.
     *
     * @var string
     */
    protected $connection = 'tenant';
}
