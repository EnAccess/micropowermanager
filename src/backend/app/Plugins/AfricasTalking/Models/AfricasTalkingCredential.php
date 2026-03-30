<?php

namespace App\Plugins\AfricasTalking\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_key
 * @property string|null $username
 * @property string|null $short_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AfricasTalkingCredential extends BaseModel {
    protected $table = 'africas_talking_credentials';
}
