<?php

namespace App\Plugins\Whatsapp\Models;

use App\Models\Base\BaseModel;

class WhatsappOptIn extends BaseModel {
    protected $table = 'whatsapp_opt_ins';
    protected $guarded = [];
    protected $casts = ['opted_in_at' => 'datetime', 'revoked_at' => 'datetime'];
}
