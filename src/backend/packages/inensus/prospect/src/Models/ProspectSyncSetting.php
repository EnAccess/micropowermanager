<?php

namespace Inensus\Prospect\Models;

use App\Models\Base\BaseModel;

class ProspectSyncSetting extends BaseModel {
    protected $table = 'prospect_sync_settings';
    protected $connection = 'tenant';
    protected $fillable = [
        'action_name',
        'sync_in_value_str',
        'sync_in_value_num',
        'max_attempts',
    ];
}
