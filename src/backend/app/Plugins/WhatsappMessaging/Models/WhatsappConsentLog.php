<?php

namespace App\Plugins\WhatsappMessaging\Models;

use App\Models\Base\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                  $id
 * @property      int                  $whatsapp_contact_id
 * @property      string               $action
 * @property      string               $source
 * @property      int|null             $user_id
 * @property      string|null          $raw_message
 * @property      Carbon|null          $created_at
 * @property      Carbon|null          $updated_at
 * @property-read WhatsappContact|null $contact
 * @property-read User|null            $user
 */
class WhatsappConsentLog extends BaseModel {
    public const ACTION_OPT_IN = 'opt_in';
    public const ACTION_OPT_OUT = 'opt_out';

    public const SOURCE_KEYWORD = 'keyword';
    public const SOURCE_OPERATOR = 'operator';

    public const array KEYWORD_ACTIONS = [
        'JOIN' => self::ACTION_OPT_IN,
        'START' => self::ACTION_OPT_IN,
        'YES' => self::ACTION_OPT_IN,
        'STOP' => self::ACTION_OPT_OUT,
        'UNSUBSCRIBE' => self::ACTION_OPT_OUT,
    ];

    public const array CONFIRMATIONS = [
        self::ACTION_OPT_IN => 'You are now subscribed to WhatsApp messages. Reply STOP to unsubscribe.',
        self::ACTION_OPT_OUT => 'You will no longer receive WhatsApp messages. Reply JOIN to subscribe again.',
    ];

    protected $table = 'whatsapp_consent_logs';

    /** @return BelongsTo<WhatsappContact, $this> */
    public function contact(): BelongsTo {
        return $this->belongsTo(WhatsappContact::class, 'whatsapp_contact_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
