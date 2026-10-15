<?php

namespace App\Plugins\WhatsApp\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $phone
 * @property int|null    $person_id
 * @property string      $preferred_channel
 * @property bool        $opted_out
 * @property string|null $reason
 * @property Carbon|null $opted_out_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WhatsAppOptOut extends BaseModel {
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_SMS = 'sms';

    protected $table = 'whatsapp_opt_outs';

    protected $fillable = [
        'phone',
        'person_id',
        'preferred_channel',
        'opted_out',
        'reason',
        'opted_out_at',
    ];

    protected $casts = [
        'opted_out' => 'boolean',
        'opted_out_at' => 'datetime',
    ];

    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }
}
