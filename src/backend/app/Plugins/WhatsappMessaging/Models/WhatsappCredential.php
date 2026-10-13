<?php

namespace App\Plugins\WhatsappMessaging\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $access_token
 * @property string|null $app_secret
 * @property string      $webhook_verify_token
 * @property string|null $phone_number_id
 * @property string|null $business_account_id
 * @property string|null $display_phone_number
 * @property string      $graph_api_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WhatsappCredential extends BaseModel {
    protected $table = 'whatsapp_credentials';
}
