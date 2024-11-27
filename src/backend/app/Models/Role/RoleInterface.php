<?php

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

interface RoleInterface {
    public function roleOwner(): HasOneOrMany;
}
