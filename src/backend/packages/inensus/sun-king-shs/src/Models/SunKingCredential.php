<?php

namespace Inensus\SunKingSHS\Models;

use App\Models\Base\BaseModel;

class SunKingCredential extends BaseModel {
    protected $table = 'sun_king_api_credentials';

    public function getClientSecret() {
        return $this->client_secret;
    }

    public function getClientId() {
        return $this->client_id;
    }

    public function getApiUrl() {
        return $this->api_url;
    }

    public function getAuthUrl() {
        return $this->auth_url;
    }

    public function getAccessToken() {
        return $this->access_token;
    }

    public function getExpirationTime() {
        return $this->token_expires_in;
    }
}
