<?php

namespace App\Plugins\CalinMeterV2\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_url
 * @property string|null $company
 * @property string|null $user_id
 * @property string|null $password
 * @property string|null $access_token
 * @property int|null    $token_expires_in
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CalinV2Credential extends BaseModel {
    protected $table = 'calin_v2_api_credentials';

    protected function casts(): array {
        return [
            'token_expires_in' => 'integer',
        ];
    }
}
