<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $username
 * @property string|null $password
 * @property bool        $is_authenticated
 * @property string      $api_url
 * @property string|null $authentication_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaCredential extends BaseModel {
    protected $table = 'steama_credentials';
}
