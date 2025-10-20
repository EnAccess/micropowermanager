<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property int     $id
 * @property ?string $api_url
 * @property ?string $username
 * @property ?string $password
 * @property ?string $authentication_token
 * @property bool    $is_authenticated
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class KelinCredential extends BaseModel {
    protected $table = 'kelin_api_credentials';
}
