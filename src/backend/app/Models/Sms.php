<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Base\BaseModel;
use Database\Factories\SmsFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class Sms.
 *
 * @property      int                         $id
 * @property      string                      $receiver
 * @property      string|null                 $trigger_type
 * @property      int|null                    $trigger_id
 * @property      string                      $body
 * @property      int                         $status
 * @property      string|null                 $uuid
 * @property      int|null                    $sender_id
 * @property      Carbon|null                 $created_at
 * @property      Carbon|null                 $updated_at
 * @property      int                         $direction
 * @property      int|null                    $gateway_id
 * @property      string|null                 $error_message
 * @property      int                         $attempts
 * @property-read Address|null                $address
 * @property-read Model|null                  $trigger
 * @property-read Collection<int, SmsAttempt> $deliveryAttempts
 */
class Sms extends BaseModel {
    /** @use HasFactory<SmsFactory> */
    use HasFactory;

    public const DIRECTION_INCOMING = 0;
    public const DIRECTION_OUTGOING = 1;

    public const STATUS_STORED = 0;
    public const STATUS_SENT = 1;
    public const STATUS_DELIVERED = 2;
    public const STATUS_FAILED = -1;
    // gateway_id names the channel sms:resend-rejected will retry it on.
    public const STATUS_WAITING_FOR_RETRY = 3;
    public const STATUS_SENDING_BY_SMS = 4;

    /**
     * @return HasMany<SmsAttempt, $this>
     */
    public function deliveryAttempts(): HasMany {
        return $this->hasMany(SmsAttempt::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function trigger(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo {
        return $this->belongsTo(Address::class, 'receiver', 'phone');
    }
}
