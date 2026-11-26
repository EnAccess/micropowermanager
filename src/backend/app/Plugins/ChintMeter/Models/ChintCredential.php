<?php

namespace App\Plugins\ChintMeter\Models;

use App\Models\Base\BaseModel;
use App\Plugins\ChintMeter\Enums\TariffAppliedBy;
use Illuminate\Support\Carbon;

/**
 * @property int             $id
 * @property string          $api_url
 * @property string|null     $user_name
 * @property string|null     $user_password
 * @property TariffAppliedBy $tariff_applied_by
 * @property Carbon|null     $created_at
 * @property Carbon|null     $updated_at
 */
class ChintCredential extends BaseModel {
    protected $table = 'chint_api_credentials';

    /**
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'tariff_applied_by' => TariffAppliedBy::class,
        ];
    }
}
