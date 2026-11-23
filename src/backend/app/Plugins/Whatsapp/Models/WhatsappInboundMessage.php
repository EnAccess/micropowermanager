<?php

namespace App\Plugins\Whatsapp\Models;

use App\Models\Base\BaseModel;

class WhatsappInboundMessage extends BaseModel {
    protected $table = 'whatsapp_inbound_messages';
    protected $guarded = [];
}
