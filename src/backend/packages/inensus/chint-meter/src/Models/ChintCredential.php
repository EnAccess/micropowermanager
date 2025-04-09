<?php

namespace Inensus\ChintMeter\Models;

use App\Models\Base\BaseModel;

class ChintCredential extends BaseModel {
    protected $table = 'chint_api_credentials';

    public function getUserName() {
        return $this->user_name;
    }

    public function getUserPassword() {
        return $this->user_password;
    }

    public function getApiUrl() {
        return $this->api_url;
    }
}
