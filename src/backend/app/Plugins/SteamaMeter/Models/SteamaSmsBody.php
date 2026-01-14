<?php

namespace App\Plugins\SteamaMeter\Models;

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
class SteamaSmsBody extends BaseModel {
    protected $table = 'steama_sms_bodies';
}
