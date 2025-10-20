<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SteamaSetting extends BaseModel {
    protected $table = 'steama_settings';

    public function setting(): MorphTo {
        return $this->morphTo();
    }
}
