<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * Class SocialTariff.
 *
 * @property int         $id
 * @property int         $tariff_id
 * @property int         $daily_allowance
 * @property float       $price
 * @property float       $initial_energy_budget
 * @property int         $maximum_stacked_energy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SocialTariff extends BaseModel {
    protected $guarded = [];
}
