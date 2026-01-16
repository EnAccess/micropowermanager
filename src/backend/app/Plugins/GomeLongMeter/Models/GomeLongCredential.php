<?php

namespace App\Plugins\GomeLongMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $user_id
 * @property string|null $user_password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
