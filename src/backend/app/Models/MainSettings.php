<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\MainSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class MainSettings.
 *
 * @property int    $id
 * @property string $site_title
 * @property string $company_name
 * @property string $currency
 * @property string $country
 * @property string $language
 * @property float  $vat_energy
 * @property float  $vat_appliance
 * */
class MainSettings extends BaseModel {
    /** @use HasFactory<MainSettingsFactory> */
    use HasFactory;
}
