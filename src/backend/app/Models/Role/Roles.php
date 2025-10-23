<?php

namespace App\Models\Role;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                 $id
 * @property      string              $role_owner_type
 * @property      int                 $role_owner_id
 * @property      int                 $role_definition_id
 * @property      Carbon|null         $created_at
 * @property      Carbon|null         $updated_at
 * @property-read RoleDefinition|null $definitions
 * @property-read Model               $roleOwner
 */
class Roles extends BaseModel {
    protected $connection = 'micro_power_manager';

    /**
     * @return BelongsTo<RoleDefinition, $this>
     */
    public function definitions(): BelongsTo {
        return $this->belongsTo(RoleDefinition::class, 'role_definition_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function roleOwner(): MorphTo {
        return $this->morphTo();
    }
}
