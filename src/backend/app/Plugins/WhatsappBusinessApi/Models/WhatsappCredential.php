<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $access_token
 * @property string|null $phone_number_id
 * @property string|null $waba_id
 * @property string|null $webhook_secret
 * @property string|null $transaction_confirmation_template
 * @property string|null $reminder_template
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WhatsappCredential extends BaseModel {
    protected $table = 'whatsapp_credentials';
    protected $guarded = [];
}
