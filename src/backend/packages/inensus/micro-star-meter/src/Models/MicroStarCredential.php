<?php

namespace Inensus\MicroStarMeter\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property int     $id
 * @property ?string $api_url
 * @property ?string $certificate_file_name
 * @property ?string $certificate_path
 * @property ?string $certificate_password
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class MicroStarCredential extends BaseModel {
    protected $table = 'micro_star_api_credentials';

    public function getApiUrl(): ?string {
        return $this->api_url;
    }
}
