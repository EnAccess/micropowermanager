<?php

namespace Inensus\CalinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $user_id
 * @property string|null $api_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CalinCredential extends BaseModel {
    protected $table = 'calin_api_credentials';
}
