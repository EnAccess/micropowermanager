<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SocialTariff
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $tariff_id
 * @property int $daily_allowance
 * @property int $price
 * @property int $initial_energy_budget
 * @property int $maximum_stacked_energy
 */
class SocialTariff extends BaseModel
{
    protected $connection = 'test_company_db';
    protected $guarded = [];
}
