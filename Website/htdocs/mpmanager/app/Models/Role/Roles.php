<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 13.07.18
 * Time: 13:46
 */

namespace App\Models\Role;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Roles extends BaseModel
{
    protected $connection = 'micro_power_manager';
    /**
     * @return belongsTo
     */
    public function definitions(): belongsTo
    {
        return $this->belongsTo(RoleDefinition::class, 'role_definition_id');
    }

    public function roleOwner(): MorphTo
    {
        return $this->morphTo();
    }
}
