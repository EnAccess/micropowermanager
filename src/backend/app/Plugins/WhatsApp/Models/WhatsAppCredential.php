<?php

namespace App\Plugins\WhatsApp\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int                                                                                       $id
 * @property string|null                                                                               $phone_number_id
 * @property string|null                                                                               $whatsapp_business_account_id
 * @property string|null                                                                               $api_access_token
 * @property string|null                                                                               $sender_phone_number
 * @property string|null                                                                               $webhook_verify_token
 * @property string                                                                                    $api_version
 * @property bool                                                                                      $is_enabled
 * @property array{payment_receipts?: bool, low_balance_warnings?: bool, token_deliveries?: bool}|null $notification_settings
 * @property Carbon|null                                                                               $created_at
 * @property Carbon|null                                                                               $updated_at
 */
class WhatsAppCredential extends BaseModel {
    protected $table = 'whatsapp_credentials';

    protected $casts = [
        'is_enabled' => 'boolean',
        'notification_settings' => 'array',
    ];

    /**
     * Preferences default to enabled so a freshly installed plugin delivers
     * every transactional message until an operator explicitly opts out.
     */
    public function isNotificationEnabled(string $notificationType): bool {
        $settings = $this->notification_settings ?? [];

        $key = match ($notificationType) {
            WhatsAppMessage::TYPE_PAYMENT_RECEIPT => 'payment_receipts',
            WhatsAppMessage::TYPE_LOW_BALANCE_WARNING => 'low_balance_warnings',
            WhatsAppMessage::TYPE_TOKEN_DELIVERY => 'token_deliveries',
            default => null,
        };

        if ($key === null) {
            return true;
        }

        return (bool) ($settings[$key] ?? true);
    }
}
