<?php

namespace Inensus\ViberMessaging\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $sms_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ViberMessage extends BaseModel {
    protected $table = 'viber_messages';
}
