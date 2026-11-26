<?php

namespace App\Plugins\AirtelSmsGateway\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $host
 * @property int|null    $port
 * @property string|null $system_id
 * @property string|null $password
 * @property string|null $source_address the sender name registered with Airtel
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AirtelSmsCredential extends BaseModel {
    protected $table = 'airtel_sms_credentials';

    /**
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'port' => 'integer',
        ];
    }
}
