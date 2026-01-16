<?php

namespace App\Plugins\TextbeeSmsGateway\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_key
 * @property string|null $device_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TextbeeCredential extends BaseModel {
    protected $table = 'textbee_credentials';
}
