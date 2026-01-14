<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $meter_reset
 * @property string|null $meter_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmSmsFeedbackWord extends BaseModel {
    protected $table = 'sm_sms_feedback_words';
}
