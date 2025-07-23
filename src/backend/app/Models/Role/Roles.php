<?php

namespace App\Models\Role;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Roles extends BaseModel {
    protected $connection = 'micro_power_manager';

    /**
     * @return BelongsTo<\App\Models\Role\RoleDefinition, $this>
     */
    public function definitions(): BelongsTo {
        return $this->belongsTo(RoleDefinition::class, 'role_definition_id');
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function roleOwner(): MorphTo {
        return $this->morphTo();
    }
}
