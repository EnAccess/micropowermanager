<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Sms.
 *
 * @property int    $id
 * @property string $trigger_type
 * @property int    $trigger_id
 * @property string $receiver
 * @property string $body
 * @property int    $status       the status of the sms 0 : waiting, 1 sent out to the receiver by sms provider
 * @property string $uuid         used for the callback to update the status of the sms
 * @property int    $direction
 * @property int    $sender_id
 */
class Sms extends BaseModel {
    use HasFactory;

    public const DIRECTION_INCOMING = 0;
    public const DIRECTION_OUTGOING = 1;

    public const STATUS_STORED = 0;
    public const STATUS_SENT = 1;
    public const STATUS_DELIVERED = 2;
    public const STATUS_FAILED = -1;

    public function trigger(): MorphTo {
        return $this->morphTo();
    }

    public function address(): BelongsTo {
        return $this->belongsTo(Address::class, 'receiver', 'phone');
    }
}
