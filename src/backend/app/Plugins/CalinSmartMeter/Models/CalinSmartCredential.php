<?php

namespace App\Plugins\CalinSmartMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $company_name
 * @property string|null $user_name
 * @property string|null $password
 * @property string|null $password_vend
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CalinSmartCredential extends BaseModel {
    protected $table = 'calin_smart_api_credentials';
}
