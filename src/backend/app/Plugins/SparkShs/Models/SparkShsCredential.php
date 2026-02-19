<?php

namespace App\Plugins\SparkShs\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $auth_url
 * @property string      $api_url
 * @property string      $client_id
 * @property string      $client_secret
 * @property string|null $access_token
 * @property Carbon|null $access_token_expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SparkShsCredential extends BaseModel {
    protected $table = 'spark_shs_credentials';
}
