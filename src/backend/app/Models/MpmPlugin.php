<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                      $id
 * @property      string                   $usage_type
 * @property      string                   $name
 * @property      string                   $description
 * @property      Carbon|null              $created_at
 * @property      Carbon|null              $updated_at
 * @property      string|null              $tail_tag
 * @property      string|null              $installation_command
 * @property      string|null              $root_class
 * @property-read Collection<int, Plugins> $plugins
 */
class MpmPlugin extends BaseModelCentral {
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
    public const AFRICAS_TALKING = 18;
    public const VODACOM_MOBILE_MONEY = 19;
    public const CHINT_METER = 20;
    public const DEMO_METER_MANUFACTURER = 21;
    public const DEMO_SHS_MANUFACTURER = 22;
    public const ODYSSEY_DATA_EXPORT = 23;
    public const PROSPECT = 24;
    public const PAYSTACK_PAYMENT_PROVIDER = 25;
    public const TEXTBEE_SMS_GATEWAY = 26;
    public const ECREEE_E_TENDER = 27;
    public const SPARK_SHS = 28;

    protected $table = 'mpm_plugins';

    /**
     * @return HasMany<Plugins, $this>
     */
    public function plugins(): HasMany {
        return $this->hasMany(Plugins::class);
    }
}
