<?php

namespace App\Plugins\PaystackPaymentProvider\Services;

use App\Plugins\PaystackPaymentProvider\Models\PaystackCredential;
use Illuminate\Support\Facades\Crypt;

class PaystackCredentialService {
    public function __construct(
        private PaystackCredential $paystackCredential,
    ) {}

    public function getCredentials(): PaystackCredential {
        $credential = $this->paystackCredential->newQuery()->first();

        if (!$credential) {
            return $this->createCredentials();
        }

        return $credential;
    }

    public function createCredentials(): PaystackCredential {
        return $this->paystackCredential->newQuery()->create([
            'secret_key' => '',
            'public_key' => '',
            'callback_url' => '',
            'merchant_name' => 'Paystack',
            'merchant_email' => null,
            'environment' => 'test',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): PaystackCredential {
        $credential = $this->getCredentials();
        if (array_key_exists('secret_key', $data)) {
            $data['secret_key'] = Crypt::encrypt($data['secret_key']);
        }
        if (array_key_exists('public_key', $data)) {
            $data['public_key'] = Crypt::encrypt($data['public_key']);
        }
        $credential->update($data);
        $credential->fresh();

        return $credential;
    }

    public function hasCredentials(): bool {
        return $this->paystackCredential->newQuery()->exists();
    }
}
