<?php

namespace Inensus\ViberMessaging\Models;

use App\Models\Base\BaseModel;

/**
 * @property string $api_token
 * @property string $webhook_url
 * @property bool   $has_webhook_created
 * @property string $deep_link
 */
class ViberCredential extends BaseModel {
    protected $table = 'viber_credentials';
}
