<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 12.07.18
 * Time: 17:33.
 */

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

interface RoleInterface
{
    public function roleOwner(): HasOneOrMany;
}
