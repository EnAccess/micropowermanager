<?php

namespace App\Plugins\DalyBms\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $user_name
 * @property string|null $password
 * @property string|null $access_token
 * @property int|null    $token_expires_in
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DalyBmsCredential extends BaseModel {
    protected $table = 'daly_bms_api_credentials';
}
