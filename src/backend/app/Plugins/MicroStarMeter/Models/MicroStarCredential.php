<?php

namespace App\Plugins\MicroStarMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_url
 * @property string|null $certificate_file_name
 * @property string|null $certificate_path
 * @property string|null $certificate_password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MicroStarCredential extends BaseModel {
    protected $table = 'micro_star_api_credentials';

    public function getApiUrl(): ?string {
        return $this->api_url;
    }
}
