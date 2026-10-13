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
 * @property bool        $sms_fallback_enabled
 * @property float|null  $sms_unit_cost
 * @property float|null  $whatsapp_utility_cost
 * @property string|null $cost_currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WhatsappCredential extends BaseModel {
    protected $table = 'whatsapp_credentials';

    protected function casts(): array {
        return [
            'sms_fallback_enabled' => 'boolean',
            'sms_unit_cost' => 'float',
            'whatsapp_utility_cost' => 'float',
        ];
    }
}
