<?php

namespace App\Plugins\AirtelSmsGateway\Models;

use App\Models\Base\BaseModel;
use App\Models\Sms;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $sms_id
 * @property      string|null $message_id the ID the SMSC assigned to the message
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Sms|null    $sms
 */
class AirtelSmsMessage extends BaseModel {
    protected $table = 'airtel_sms_messages';

    /**
     * @return BelongsTo<Sms, $this>
     */
    public function sms(): BelongsTo {
        return $this->belongsTo(Sms::class);
    }
}
