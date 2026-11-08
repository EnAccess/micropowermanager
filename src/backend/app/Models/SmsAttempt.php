<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One hand-off of an sms row to one channel. A message can have several: a WhatsApp try, its retry, the SMS fallback and its resend.
 *
 * @property      int                       $id
 * @property      int                       $sms_id
 * @property      int                       $gateway_id
 * @property      string|null               $external_id
 * @property      string|null               $message_type
 * @property      string|null               $template_name
 * @property      array<string, mixed>|null $request
 * @property      string                    $status
 * @property      int|null                  $error_code
 * @property      string|null               $error_message
 * @property      Carbon|null               $sent_at
 * @property      Carbon|null               $delivered_at
 * @property      Carbon|null               $read_at
 * @property      Carbon|null               $failed_at
 * @property      Carbon|null               $created_at
 * @property      Carbon|null               $updated_at
 * @property-read Sms|null                  $sms
 */
class SmsAttempt extends BaseModel {
    public const MESSAGE_TYPE_TEXT = 'text';
    public const MESSAGE_TYPE_TEMPLATE = 'template';

    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    // Failed is left out on purpose: it is terminal and never compared against progress.
    private const array STATUS_RANKS = [self::STATUS_ACCEPTED => 0, self::STATUS_SENT => 1, self::STATUS_DELIVERED => 2, self::STATUS_READ => 3];

    protected $table = 'sms_attempts';

    // The request can hold a customer's token, so it stays out of API responses.
    protected $hidden = ['request'];

    protected function casts(): array {
        return ['request' => 'array', 'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    /** @return BelongsTo<Sms, $this> */
    public function sms(): BelongsTo {
        return $this->belongsTo(Sms::class);
    }

    /**
     * Channels neither order nor de-duplicate status events, so an attempt only moves forward, and a failure after delivery is ignored.
     */
    public function canMoveTo(string $status): bool {
        $targetRank = $status === self::STATUS_FAILED ? self::STATUS_RANKS[self::STATUS_DELIVERED] : self::STATUS_RANKS[$status] ?? null;

        return $this->status !== self::STATUS_FAILED && $targetRank !== null && self::STATUS_RANKS[$this->status] < $targetRank;
    }
}
