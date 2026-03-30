<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $meter_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaSmsFeedbackWord extends BaseModel {
    protected $table = 'steama_sms_feedback_words';
}
