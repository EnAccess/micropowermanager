<?php

namespace App\Plugins\WhatsApp\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                   $id
 * @property      string|null           $message_id
 * @property      int|null              $person_id
 * @property      int|null              $transaction_id
 * @property      int|null              $template_id
 * @property      string                $recipient_phone
 * @property      string|null           $recipient_name
 * @property      string                $notification_type
 * @property      string|null           $reference
 * @property      string                $status
 * @property      string|null           $body
 * @property      string|null           $error_message
 * @property      Carbon|null           $sent_at
 * @property      Carbon|null           $delivered_at
 * @property      Carbon|null           $read_at
 * @property      Carbon|null           $created_at
 * @property      Carbon|null           $updated_at
 * @property-read Person|null           $person
 * @property-read Transaction|null      $transaction
 * @property-read WhatsAppTemplate|null $template
 */
class WhatsAppMessage extends BaseModel {
    public const TYPE_PAYMENT_RECEIPT = 'payment_receipt';
    public const TYPE_LOW_BALANCE_WARNING = 'low_balance_warning';
    public const TYPE_TOKEN_DELIVERY = 'token_delivery';
    public const TYPE_TEST = 'test';
    public const TYPE_CUSTOM = 'custom';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    /** @var array<string, string> */
    public const TYPES = [
        self::TYPE_PAYMENT_RECEIPT => 'Payment receipt',
        self::TYPE_LOW_BALANCE_WARNING => 'Low-balance warning',
        self::TYPE_TOKEN_DELIVERY => 'Token delivery',
        self::TYPE_TEST => 'Test message',
        self::TYPE_CUSTOM => 'Custom',
    ];

    protected $table = 'whatsapp_messages';

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<WhatsAppTemplate, $this>
     */
    public function template(): BelongsTo {
        return $this->belongsTo(WhatsAppTemplate::class);
    }

    public function statusLabel(): string {
        return ucfirst($this->status);
    }

    public function typeLabel(): string {
        return self::TYPES[$this->notification_type] ?? ucfirst(str_replace('_', ' ', $this->notification_type));
    }
}
