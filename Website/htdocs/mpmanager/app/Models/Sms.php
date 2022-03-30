<?php

namespace App\Models;

use App\Models\Address\Address;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Sms
 *
 * @package  App
 * @property integer $id
 * @property string $trigger_type
 * @property int $trigger_id
 * @property string $receiver
 * @property string $body
 * @property int $status the status of the sms 0 : waiting, 1 sent out to the receiver by sms provider
 * @property string $uuid used for the callback to update the status of the sms
 * @property int $direction
 * @property int $sender_id
 */
class Sms extends BaseModel
{
    protected $connection = 'test_company_db';
    public function trigger(): MorphTo
    {
        return $this->morphTo();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'receiver', 'phone');
    }
}
