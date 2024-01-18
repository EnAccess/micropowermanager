<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property null|string $tail_tag
 * @property null|string $installation_command
 */
class MpmPlugin extends MasterModel
{
    use HasFactory;

    public const SPARK_METER = 1;
    public const STEAMACO_METER = 2;
    public const CALIN_METER = 3;
    public const CALIN_SMART_METER = 4;
    public const KELIN_METER = 5;
    public const STRON_METER = 6;
    public const SWIFTA_PAYMENT_PROVIDER = 7;
    public const MESOMB_PAYMENT_PROVIDER = 8;
    public const BULK_REGISTRATION = 9;
    public const VIBER_MESSAGING = 10;
    public const WAVE_MONEY_PAYMENT_PROVIDER = 11;
    public const MICRO_STAR_METERS = 12;
    public const SUN_KING_SHS = 13;
    public const GOME_LONG_METERS = 14;
    public const WAVECOM_PAYMENT_PROVIDER = 15;
    public const DALY_BMS = 16;
    public const AGAZA_SHS = 17;

    protected $table = 'mpm_plugins';

    //has many used
    public function plugins()
    {
        return $this->hasMany(Plugins::class);
    }
}
