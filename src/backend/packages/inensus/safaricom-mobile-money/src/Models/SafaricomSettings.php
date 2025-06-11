<?php

namespace Inensus\SafaricomMobileMoney\Models;

use App\Models\Base\BaseModel;

class SafaricomSettings extends BaseModel {
    protected $table = 'safaricom_settings';

    protected $fillable = [
        'consumer_key',
        'consumer_secret',
        'passkey',
        'shortcode',
        'environment',
        'validation_url',
        'confirmation_url',
        'timeout_url',
        'result_url',
    ];

    protected $hidden = [
        'consumer_key',
        'consumer_secret',
        'passkey',
    ];
}
