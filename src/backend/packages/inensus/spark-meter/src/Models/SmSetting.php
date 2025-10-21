<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmSetting extends BaseModel {
    protected $table = 'sm_settings';

    public function setting(): MorphTo {
        return $this->morphTo();
    }
}
