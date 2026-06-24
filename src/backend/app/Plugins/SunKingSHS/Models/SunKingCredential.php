<?php

namespace App\Plugins\SunKingSHS\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $auth_url
 * @property string      $api_url
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property string|null $access_token
 * @property int|null    $token_expires_in
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SunKingCredential extends BaseModel {
    protected $table = 'sun_king_api_credentials';
}
