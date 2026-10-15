<?php

namespace App\Plugins\WhatsApp\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $message_id
 * @property string      $recipient_phone
 * @property string      $notification_type
 * @property string      $status
 * @property string|null $body
 * @property string|null $error_message
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon|null $provider_sent_at
 * @property Carbon|null $provider_status_at
 * @property Carbon|null $delivery_status_updated_at
 * @property Carbon|null $failed_at
 * @property string|null $provider_error_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
        'provider_sent_at' => 'datetime',
        'provider_status_at' => 'datetime',
        'delivery_status_updated_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
