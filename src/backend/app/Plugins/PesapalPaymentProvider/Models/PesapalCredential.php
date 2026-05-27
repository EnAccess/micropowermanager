<?php

namespace App\Plugins\PesapalPaymentProvider\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $consumer_key
 * @property string      $consumer_secret
 * @property string|null $callback_url
 * @property string      $merchant_name
 * @property string|null $merchant_email
 * @property string      $environment
 * @property string      $currency
 * @property string|null $ipn_id
 * @property Carbon|null $ipn_registered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PesapalCredential extends BaseModel {
    protected $table = 'pesapal_credentials';

    protected $casts = [
        'ipn_registered_at' => 'datetime',
    ];

    public function getConsumerKey(): string {
        return $this->consumer_key ?? '';
    }

    public function getConsumerSecret(): string {
        return $this->consumer_secret ?? '';
    }

    public function getMerchantName(): ?string {
        return $this->merchant_name;
    }

    public function getMerchantEmail(): ?string {
        return $this->merchant_email;
    }

    public function getCallbackUrl(): ?string {
        return $this->callback_url;
    }

    public function getEnvironment(): string {
        return $this->environment;
    }

    public function isLive(): bool {
        return $this->environment === 'live';
    }

    public function isTest(): bool {
        return $this->environment === 'test';
    }

    public function getCurrency(): string {
        return $this->currency;
    }

    public function getIpnId(): ?string {
        return $this->ipn_id;
    }

    public function getIpnRegisteredAt(): ?Carbon {
        return $this->ipn_registered_at;
    }
}
