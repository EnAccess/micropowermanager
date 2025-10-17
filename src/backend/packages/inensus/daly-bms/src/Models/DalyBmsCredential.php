<?php

namespace Inensus\DalyBms\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property int    $id
 * @property string $api_url
 * @property string $user_name
 * @property string $password
 * @property string $access_token
 * @property int    $token_expires_in
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DalyBmsCredential extends BaseModel {
    protected $table = 'daly_bms_api_credentials';

    public function getUserName(): string {
        return $this->user_name;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getApiUrl(): string {
        return $this->api_url;
    }

    public function getAccessToken(): string {
        return $this->access_token;
    }

    public function getExpirationTime(): int {
        return $this->token_expires_in;
    }
}
