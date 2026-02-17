<?php

namespace App\Plugins\ChintMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $user_name
 * @property string|null $user_password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
