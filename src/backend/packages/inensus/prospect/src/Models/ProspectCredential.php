<?php

namespace Inensus\Prospect\Models;

use App\Models\Base\BaseModel;

/**
 * @property string      $api_url
 * @property string|null $api_token
 */
class ProspectCredential extends BaseModel {
    protected $table = 'prospect_credentials';

    protected $fillable = [
        'api_url',
        'api_token',
    ];
}
