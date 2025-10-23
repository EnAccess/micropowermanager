<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_url
 * @property string|null $username
 * @property string|null $password
 * @property string|null $authentication_token
 * @property bool        $is_authenticated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class KelinCredential extends BaseModel {
    protected $table = 'kelin_api_credentials';
}
