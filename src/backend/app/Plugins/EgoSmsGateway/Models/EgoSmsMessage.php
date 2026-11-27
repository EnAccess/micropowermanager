<?php

namespace App\Plugins\EgoSmsGateway\Models;

use App\Models\Base\BaseModel;
use App\Models\Sms;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $sms_id
 * @property      string|null $follow_up_code
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Sms|null    $sms
 */
class EgoSmsMessage extends BaseModel {
    protected $table = 'ego_sms_messages';

    /**
     * @return BelongsTo<Sms, $this>
     */
    public function sms(): BelongsTo {
        return $this->belongsTo(Sms::class);
    }
}
