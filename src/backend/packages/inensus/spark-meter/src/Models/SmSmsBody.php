<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $reference
 * @property string|null $title
 * @property string|null $body
 * @property string      $place_holder
 * @property string      $variables
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmSmsBody extends BaseModel {
    protected $table = 'sm_sms_bodies';
}
