<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Log extends BaseModel {
    public function owner(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function affected(): MorphTo {
        return $this->morphTo();
    }
}
