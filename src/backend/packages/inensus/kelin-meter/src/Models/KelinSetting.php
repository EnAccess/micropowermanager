<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int              $id
 * @property int              $setting_id
 * @property string           $setting_type
 * @property Carbon           $created_at
 * @property Carbon           $updated_at
 * @property KelinSyncSetting $setting
 */
class KelinSetting extends BaseModel {
    protected $table = 'kelin_settings';

    /**
     * @return MorphTo<Model, $this>
     */
    public function setting(): MorphTo {
        return $this->morphTo();
    }
}
