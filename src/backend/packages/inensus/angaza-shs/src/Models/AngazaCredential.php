<?php

namespace Inensus\AngazaSHS\Models;

use App\Models\Base\BaseModel;

class AngazaCredential extends BaseModel {
    protected $table = 'angaza_api_credentials';

    public function getClientSecret() {
        return $this->client_secret;
    }

    public function getClientId() {
        return $this->client_id;
    }

    public function getApiUrl() {
        return $this->api_url;
    }
}
