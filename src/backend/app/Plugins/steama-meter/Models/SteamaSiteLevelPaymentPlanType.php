<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaSiteLevelPaymentPlanType extends BaseModel {
    protected $table = 'steama_site_level_payment_plan_types';
}
