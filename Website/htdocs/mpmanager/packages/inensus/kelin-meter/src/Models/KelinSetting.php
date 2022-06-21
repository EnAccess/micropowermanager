<?php

namespace Inensus\KelinMeter\Models;

use App\Models\BaseModel;
use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KelinSetting extends BaseModel
{
    protected $table = 'kelin_settings';

    public function setting(): morphTo
    {
        return $this->morphTo();
    }


}
