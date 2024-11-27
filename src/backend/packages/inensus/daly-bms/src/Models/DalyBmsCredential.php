<?php

namespace Inensus\DalyBms\Models;

use App\Models\Base\BaseModel;

class DalyBmsCredential extends BaseModel {
    protected $table = 'daly_bms_api_credentials';

    public function getUserName() {
        return $this->user_name;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getApiUrl() {
        return $this->api_url;
    }

    public function getAccessToken() {
        return $this->access_token;
    }

    public function getExpirationTime() {
        return $this->token_expires_in;
    }
}
