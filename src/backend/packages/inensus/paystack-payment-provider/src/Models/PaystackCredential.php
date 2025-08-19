<?php

namespace Inensus\PaystackPaymentProvider\Models;

use App\Models\Base\BaseModel;

/**
 * @property string secret_key
 * @property string public_key
 * @property string|null webhook_secret
 * @property string|null callback_url
 * @property string|null merchant_name
 * @property string environment
 */
class PaystackCredential extends BaseModel {
    protected $table = 'paystack_credentials';

    public function getSecretKey(): string {
        return $this->secret_key;
    }

    public function getPublicKey(): string {
        return $this->public_key;
    }

    public function getWebhookSecret(): ?string {
        return $this->webhook_secret;
    }

    public function getMerchantName(): ?string {
        return $this->merchant_name;
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
}
