<?php

namespace Inensus\Prospect\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_url
 * @property string|null $api_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProspectCredential extends BaseModel {
    protected $table = 'prospect_credentials';

    protected $fillable = [
        'api_url',
        'api_token',
    ];
}
