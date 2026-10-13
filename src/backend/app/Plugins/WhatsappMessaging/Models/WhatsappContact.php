<?php

namespace App\Plugins\WhatsappMessaging\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                                 $id
 * @property      int|null                            $person_id
 * @property      string                              $phone
 * @property      string|null                         $wa_id
 * @property      string                              $opt_in_status
 * @property      Carbon|null                         $opted_in_at
 * @property      Carbon|null                         $opted_out_at
 * @property      Carbon|null                         $last_inbound_at
 * @property      Carbon|null                         $opt_in_prompted_at
 * @property      Carbon|null                         $created_at
 * @property      Carbon|null                         $updated_at
 * @property-read Person|null                         $person
 * @property-read Collection<int, WhatsappConsentLog> $consentLogs
 * @property-read Collection<int, WhatsappMessage>    $messages
 */
class WhatsappContact extends BaseModel {
    public const OPT_IN_STATUS_OPTED_IN = 'opted_in';
    public const OPT_IN_STATUS_OPTED_OUT = 'opted_out';

    protected $table = 'whatsapp_contacts';

    protected function casts(): array {
        return [
            'opted_in_at' => 'datetime',
            'opted_out_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'opt_in_prompted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return HasMany<WhatsappConsentLog, $this>
     */
    public function consentLogs(): HasMany {
        return $this->hasMany(WhatsappConsentLog::class, 'whatsapp_contact_id');
    }

    /**
     * @return HasMany<WhatsappMessage, $this>
     */
    public function messages(): HasMany {
        return $this->hasMany(WhatsappMessage::class, 'whatsapp_contact_id');
    }
}
