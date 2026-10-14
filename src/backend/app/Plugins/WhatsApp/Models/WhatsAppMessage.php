<?php

namespace App\Plugins\WhatsApp\Models;

use App\Models\Base\BaseModel;
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
