<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property null|string $tail_tag
 * @property null|string $installation_command
 */
class MpmPlugin extends MasterModel
{
    const SPARK_METER = 1;
    const STEAMACO_METER = 2;
    const CALIN_METER = 3;
    const CALIN_SMART_METER = 4;
    const KELIN_METER = 5;
    const STRON_METER = 6;
    const SWIFTA_PAYMENT_PROVIDER = 7;
    const MESOMB_PAYMENT_PROVIDER = 8;
    const BULK_REGISTRATION = 9;
    const VIBER_MESSAGING = 10;
    const WAVE_MONEY_PAYMENT_PROVIDER = 11;
    const MICRO_STAR_METERS = 12;
    const SUN_KING_METERS = 13;
    const GOME_LONG_METERS = 14;

    use HasFactory;

    protected $table = 'mpm_plugins';

    //has many used
    public function plugins()
    {
        return $this->hasMany(Plugins::class);
    }
}
