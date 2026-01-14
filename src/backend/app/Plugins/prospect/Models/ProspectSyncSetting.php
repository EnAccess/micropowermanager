<?php

namespace App\Plugins\Prospect\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * Class ProspectSyncSetting.
 *
 * @property int         $id
 * @property string      $action_name
 * @property string      $sync_in_value_str
 * @property int         $sync_in_value_num
 * @property int         $max_attempts
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProspectSyncSetting extends BaseModel {
    protected $table = 'prospect_sync_settings';
    protected $connection = 'tenant';
    protected $fillable = [
        'action_name',
        'is_enabled',
        'sync_in_value_str',
        'sync_in_value_num',
        'max_attempts',
    ];
}
