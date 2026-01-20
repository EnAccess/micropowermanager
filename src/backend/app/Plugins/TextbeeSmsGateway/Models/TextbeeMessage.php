<?php

namespace App\Plugins\TextbeeSmsGateway\Models;

use App\Models\Base\BaseModel;
use App\Models\Sms;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $sms_id
 * @property      string      $message_id
 * @property      string      $status
 * @property      string|null $created_at_textbee
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Sms|null    $sms
 */
class TextbeeMessage extends BaseModel {
    protected $table = 'textbee_messages';

    /**
     * @return BelongsTo<Sms, $this>
     */
    public function sms(): BelongsTo {
        return $this->belongsTo(Sms::class);
    }
}
