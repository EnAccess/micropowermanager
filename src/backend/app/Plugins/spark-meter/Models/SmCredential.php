<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $api_key
 * @property string|null $api_secret
 * @property bool        $is_authenticated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmCredential extends BaseModel {
    protected $table = 'sm_api_credentials';
}
