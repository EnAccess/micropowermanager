<?php

namespace Inensus\PaystackPaymentProvider\Services;

use Inensus\PaystackPaymentProvider\Models\PaystackCredential;

class PaystackCredentialService {
    public function __construct(
        private PaystackCredential $paystackCredential,
    ) {}

    public function getCredentials(): PaystackCredential {
        return $this->paystackCredential->newQuery()->first();
    }

    public function createCredentials(): PaystackCredential {
        return $this->paystackCredential->newQuery()->create([
            'secret_key' => '',
            'public_key' => '',
            'webhook_secret' => '',
            'callback_url' => '',
            'merchant_name' => 'Paystack',
            'environment' => 'test',
        ]);
    }

    public function updateCredentials(array $data): PaystackCredential {
        $credential = $this->getCredentials();
        $credential->update($data);
        $credential->fresh();

        return $credential;
    }

    public function hasCredentials(): bool {
        return $this->paystackCredential->newQuery()->exists();
    }
}
