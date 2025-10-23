<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\MainSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class MainSettings.
 *
 * @property int         $id
 * @property string      $usage_type
 * @property string      $site_title
 * @property string      $company_name
 * @property string      $currency
 * @property string      $country
 * @property string      $language
 * @property float|null  $vat_energy
 * @property float|null  $vat_appliance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $protected_page_password
 */
class MainSettings extends BaseModel {
    /** @use HasFactory<MainSettingsFactory> */
    use HasFactory;
}
