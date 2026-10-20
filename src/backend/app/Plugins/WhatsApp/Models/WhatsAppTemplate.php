<?php

namespace App\Plugins\WhatsApp\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $type
 * @property string      $language
 * @property string      $body
 * @property string      $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WhatsAppTemplate extends BaseModel {
    public const TYPE_PAYMENT_RECEIPT = 'payment_receipt';
    public const TYPE_LOW_BALANCE_WARNING = 'low_balance_warning';
    public const TYPE_TOKEN_DELIVERY = 'token_delivery';
    public const TYPE_CUSTOM = 'custom';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';

    /** @var array<string, string> */
    public const TYPES = [
        self::TYPE_PAYMENT_RECEIPT => 'Payment receipt',
        self::TYPE_LOW_BALANCE_WARNING => 'Low-balance warning',
        self::TYPE_TOKEN_DELIVERY => 'Token delivery',
        self::TYPE_CUSTOM => 'Custom',
    ];

    /** @var array<string, string> */
    public const DEFAULT_BODIES = [
        self::TYPE_PAYMENT_RECEIPT => 'Hello {{customer_name}}, we have received your payment of {{payment_amount}} {{currency}} on {{payment_date}}. Transaction reference: {{transaction_id}}.',
        self::TYPE_LOW_BALANCE_WARNING => 'Hello {{customer_name}}, your outstanding balance is {{balance}} {{currency}}. Please settle it before {{due_date}}.',
        self::TYPE_TOKEN_DELIVERY => 'Hello {{customer_name}}, your token is {{token}}. Thank you for your payment.',
    ];

    protected $table = 'whatsapp_templates';

    public static function activeFor(string $type): ?self {
        return self::query()
            ->where('type', $type)
            ->where('status', self::STATUS_ACTIVE)
            ->latest('id')
            ->first();
    }

    public static function defaultBodyFor(string $type): string {
        return self::DEFAULT_BODIES[$type] ?? '';
    }

    public static function labelToType(string $label): string {
        $type = array_search($label, self::TYPES, true);
        if ($type !== false) {
            return $type;
        }

        $slug = str_replace(['-', ' '], '_', strtolower($label));

        return array_key_exists($slug, self::TYPES) ? $slug : self::TYPE_CUSTOM;
    }

    public function typeLabel(): string {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
