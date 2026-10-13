<?php

namespace App\Plugins\WhatsappMessaging\Models;

use App\Models\Base\BaseModel;
use App\Models\MpmPlugin;
use App\Models\Sms;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                  $id
 * @property      int                  $sms_id
 * @property      int|null             $whatsapp_contact_id
 * @property      string|null          $wamid
 * @property      string               $message_type
 * @property      string|null          $template_name
 * @property      string|null          $template_language
 * @property      string               $status
 * @property      int|null             $error_code
 * @property      string|null          $error_message
 * @property      Carbon|null          $sent_at
 * @property      Carbon|null          $delivered_at
 * @property      Carbon|null          $read_at
 * @property      Carbon|null          $failed_at
 * @property      string|null          $pricing_category
 * @property      Carbon|null          $created_at
 * @property      Carbon|null          $updated_at
 * @property-read Sms|null             $sms
 * @property-read WhatsappContact|null $contact
 */
class WhatsappMessage extends BaseModel {
    public const MESSAGE_TYPE_TEXT = 'text';
    public const MESSAGE_TYPE_TEMPLATE = 'template';

    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [self::STATUS_ACCEPTED, self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_READ, self::STATUS_FAILED];

    // Failed is left out on purpose: it is terminal and never compared against progress.
    private const array STATUS_RANKS = [
        self::STATUS_ACCEPTED => 0,
        self::STATUS_SENT => 1,
        self::STATUS_DELIVERED => 2,
        self::STATUS_READ => 3,
    ];

    public const OUTCOME_DELIVERED_WHATSAPP = 'delivered_whatsapp';
    public const OUTCOME_FELL_BACK = 'fell_back';
    public const OUTCOME_FAILED = 'failed';
    public const OUTCOME_PENDING = 'pending';

    public const OUTCOMES = [self::OUTCOME_DELIVERED_WHATSAPP, self::OUTCOME_FELL_BACK, self::OUTCOME_FAILED, self::OUTCOME_PENDING];

    public const OUTCOME_FINAL_CHANNELS = [
        self::OUTCOME_DELIVERED_WHATSAPP => 'whatsapp',
        self::OUTCOME_FELL_BACK => 'sms',
        self::OUTCOME_FAILED => null,
        self::OUTCOME_PENDING => 'whatsapp',
    ];

    protected $table = 'whatsapp_messages';

    protected function casts(): array {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Sms, $this>
     */
    public function sms(): BelongsTo {
        return $this->belongsTo(Sms::class);
    }

    /**
     * @return BelongsTo<WhatsappContact, $this>
     */
    public function contact(): BelongsTo {
        return $this->belongsTo(WhatsappContact::class, 'whatsapp_contact_id');
    }

    public function hasReached(string $status): bool {
        $currentRank = self::STATUS_RANKS[$this->status] ?? null;
        $statusRank = self::STATUS_RANKS[$status] ?? null;

        return $currentRank !== null && $statusRank !== null && $currentRank >= $statusRank;
    }

    /**
     * The single definition of a message's outcome, shared by the message list, its filter and the summary.
     * A message fell back when WhatsApp failed and an SMS gateway then sent the sms row; it failed when both failed.
     * Requires the sms join from joinSms().
     */
    public static function outcomeExpression(): string {
        return sprintf(
            "CASE
                WHEN whatsapp_messages.status IN ('%s', '%s') THEN '%s'
                WHEN whatsapp_messages.status = '%s' AND sms.gateway_id <> %d AND sms.status IN (%d, %d) THEN '%s'
                WHEN whatsapp_messages.status = '%s' AND sms.status = %d THEN '%s'
                ELSE '%s'
            END",
            self::STATUS_DELIVERED,
            self::STATUS_READ,
            self::OUTCOME_DELIVERED_WHATSAPP,
            self::STATUS_FAILED,
            MpmPlugin::WHATSAPP_MESSAGING,
            Sms::STATUS_SENT,
            Sms::STATUS_DELIVERED,
            self::OUTCOME_FELL_BACK,
            self::STATUS_FAILED,
            Sms::STATUS_FAILED,
            self::OUTCOME_FAILED,
            self::OUTCOME_PENDING,
        );
    }

    /**
     * @param Builder<WhatsappMessage> $query
     *
     * @return Builder<WhatsappMessage>
     */
    #[Scope]
    protected function joinSms(Builder $query): Builder {
        return $query->join('sms', 'sms.id', '=', 'whatsapp_messages.sms_id');
    }

    /**
     * @param Builder<WhatsappMessage> $query
     *
     * @return Builder<WhatsappMessage>
     */
    #[Scope]
    protected function withOutcome(Builder $query): Builder {
        return $query->select('whatsapp_messages.*')->selectRaw('('.self::outcomeExpression().') AS outcome');
    }

    /**
     * @param Builder<WhatsappMessage> $query
     *
     * @return Builder<WhatsappMessage>
     */
    #[Scope]
    protected function whereOutcome(Builder $query, string $outcome): Builder {
        return $query->whereRaw('('.self::outcomeExpression().') = ?', [$outcome]);
    }
}
