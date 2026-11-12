<?php

namespace App\Plugins\Whatsapp\Models;

use App\Models\Base\BaseModel;

class WhatsappMessage extends BaseModel {
    protected $table = 'whatsapp_messages';
    protected $guarded = [];
}
