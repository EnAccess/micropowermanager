<?php

namespace App\Plugins\EgoSmsGateway\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $username
 * @property string|null $password
 * @property string|null $sender_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EgoSmsCredential extends BaseModel {
    protected $table = 'ego_sms_credentials';
}
