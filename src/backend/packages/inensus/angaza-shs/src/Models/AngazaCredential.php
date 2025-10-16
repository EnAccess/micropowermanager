<?php

namespace Inensus\AngazaSHS\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property int    $id
 * @property string $api_url
 * @property string $client_id
 * @property string $client_secret
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AngazaCredential extends BaseModel {
    protected $table = 'angaza_api_credentials';

    public function getClientSecret(): string {
        return $this->client_secret;
    }

    public function getClientId(): string {
        return $this->client_id;
    }

    public function getApiUrl(): string {
        return $this->api_url;
    }
}
