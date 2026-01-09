<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property float       $threshold
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaMinimumTopUpRequirementsPaymentPlan extends BaseModel {
    protected $table = 'steama_minimum_top_up_requirements_payment_plans';
}
