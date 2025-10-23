<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      string      $affected_type
 * @property      int         $affected_id
 * @property      int         $user_id
 * @property      string      $action
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Model       $affected
 * @property-read User|null   $owner
 */
class Log extends BaseModel {
    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function affected(): MorphTo {
        return $this->morphTo();
    }
}
