<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 13.07.18
 * Time: 13:46.
 */

namespace App\Models\Role;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Roles extends BaseModel
{
    protected $connection = 'micro_power_manager';

    /**
     * @return BelongsTo
     */
    public function definitions(): BelongsTo
    {
        return $this->belongsTo(RoleDefinition::class, 'role_definition_id');
    }

    public function roleOwner(): MorphTo
    {
        return $this->morphTo();
    }
}
