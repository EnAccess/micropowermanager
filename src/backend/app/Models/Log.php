<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Log extends BaseModel {
    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function affected(): MorphTo {
        return $this->morphTo();
    }
}
