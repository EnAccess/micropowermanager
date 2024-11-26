<?php

namespace Inensus\GomeLongMeter\Models;

use App\Models\Base\BaseModel;

class GomeLongCredential extends BaseModel {
    protected $table = 'gome_long_api_credentials';

    public function getUserId() {
        return $this->user_id;
    }

    public function getUserPassword() {
        return $this->user_password;
    }

    public function getApiUrl() {
        return $this->api_url;
    }
}
