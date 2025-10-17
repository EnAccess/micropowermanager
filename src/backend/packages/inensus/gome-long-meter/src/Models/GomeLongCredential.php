<?php

namespace Inensus\GomeLongMeter\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property int     $id
 * @property string  $api_url
 * @property ?string $user_id
 * @property ?string $user_password
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class GomeLongCredential extends BaseModel {
    protected $table = 'gome_long_api_credentials';

    public function getUserId(): ?string {
        return $this->user_id;
    }

    public function getUserPassword(): ?string {
        return $this->user_password;
    }

    public function getApiUrl(): string {
        return $this->api_url;
    }
}
