<?php

namespace Inensus\ChintMeter\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property int    $id
 * @property string $api_url
 * @property string $user_name
 * @property string $user_password
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ChintCredential extends BaseModel {
    protected $table = 'chint_api_credentials';

    public function getUserName(): string {
        return $this->user_name;
    }

    public function getUserPassword(): string {
        return $this->user_password;
    }

    public function getApiUrl(): string {
        return $this->api_url;
    }
}
