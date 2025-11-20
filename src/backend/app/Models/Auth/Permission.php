<?php

namespace App\Models\Auth;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission {
    /**
     * Use tenant connection for Spatie Permission tables.
     *
     * @var string
     */
    protected $connection = 'tenant';
}
