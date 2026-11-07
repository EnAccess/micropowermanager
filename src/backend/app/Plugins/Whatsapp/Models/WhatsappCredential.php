<?php

namespace App\Plugins\Whatsapp\Models;

use App\Models\Base\BaseModel;

class WhatsappCredential extends BaseModel {
    protected $table = 'whatsapp_credentials';
    protected $guarded = [];
    protected $casts = ['fallback_to_sms' => 'boolean'];
}
