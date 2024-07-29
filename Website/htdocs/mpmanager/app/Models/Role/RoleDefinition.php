<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 13.07.18
 * Time: 13:46.
 */

namespace App\Models\Role;

use App\Models\Base\BaseModel;

class RoleDefinition extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'micro_power_manager';

    public function roles(): void
    {
        $this->hasMany(Roles::class);
    }
}
