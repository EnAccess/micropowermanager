<?php

namespace Inensus\AfricasTalking\Models;

use App\Models\Base\BaseModel;
use App\Models\Sms;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AfricasTalkingMessage extends BaseModel {
    protected $table = 'africas_talking_messages';

    public function sms(): BelongsTo {
        return $this->belongsTo(Sms::class);
    }
}
