<?php

namespace Inensus\StronMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $api_token
 * @property string|null $company_name
 * @property string|null $username
 * @property string|null $password
 * @property bool        $is_authenticated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StronCredential extends BaseModel {
    protected $table = 'stron_api_credentials';
}
