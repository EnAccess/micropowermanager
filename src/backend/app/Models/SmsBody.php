<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\SmsBodyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
class SmsBody extends BaseModel {
    /** @use HasFactory<SmsBodyFactory> */
    use HasFactory;
    protected $table = 'sms_bodies';
}
