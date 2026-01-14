<?php

namespace Inensus\DalyBms\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $user_name
 * @property string|null $password
 * @property string|null $access_token
 * @property int|null    $token_expires_in
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
