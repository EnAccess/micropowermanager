<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KelinSetting extends BaseModel {
    protected $table = 'kelin_settings';

    public function setting(): MorphTo {
        return $this->morphTo();
    }
}
