<?php

namespace Inensus\MicroStarMeter\Models;

use App\Models\Base\BaseModel;

class MicroStarCredential extends BaseModel {
    protected $table = 'micro_star_api_credentials';

    public function getApiUrl() {
        return $this->api_url;
    }
}
