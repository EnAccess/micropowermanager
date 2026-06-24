<?php

namespace App\Plugins\AngazaSHS\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $api_url
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AngazaCredential extends BaseModel {
    protected $table = 'angaza_api_credentials';
}
