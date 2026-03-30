<?php

namespace App\Plugins\ViberMessaging\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_token
 * @property string|null $webhook_url
 * @property bool        $has_webhook_created
 * @property string|null $deep_link
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ViberCredential extends BaseModel {
    protected $table = 'viber_credentials';
}
