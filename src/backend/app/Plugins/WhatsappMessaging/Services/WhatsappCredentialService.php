<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappConnectionFailedException;
use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Traits\EncryptsCredentials;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WhatsappCredentialService {
    use EncryptsCredentials;

    private const ENCRYPTED_FIELDS = ['access_token', 'app_secret', 'webhook_verify_token'];

    /**
     * Secrets the operator may leave empty on update; the stored value is kept so
     * the UI never has to resend (or even know) them.
     */
    private const KEEP_WHEN_EMPTY_FIELDS = ['access_token', 'app_secret', 'webhook_verify_token'];

    public function __construct(
        private WhatsappCredential $credential,
        private WhatsappApiClient $apiClient,
    ) {}

    public function createCredentials(): WhatsappCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'access_token' => null,
            'app_secret' => null,
            'webhook_verify_token' => $this->encryptCredentialField(Str::random(40)),
            'phone_number_id' => null,
            'business_account_id' => null,
        ]);
    }

    public function getCredentials(): ?WhatsappCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): WhatsappCredential {
        $credential = $this->credential->newQuery()->firstOrFail();

        foreach (self::KEEP_WHEN_EMPTY_FIELDS as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === null || $data[$field] === '')) {
                unset($data[$field]);
            }
        }

        $credential->update($this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS));

        return $this->decryptCredentialFields($credential->fresh(), self::ENCRYPTED_FIELDS);
    }

    public function isConfigured(): bool {
        $credential = $this->credential->newQuery()->first();

        return $credential instanceof WhatsappCredential
            && !empty($credential->access_token)
            && !empty($credential->phone_number_id)
            && !empty($credential->app_secret);
    }

    /**
     * @return array{display_phone_number: string, verified_name: string|null, quality_rating: string|null}
     *
     * @throws WhatsappConnectionFailedException
     */
    public function testConnection(): array {
        if (!$this->isConfigured()) {
            throw ValidationException::withMessages([
                'credential' => 'WhatsApp credentials are incomplete. Set the access token, app secret and phone number ID first.',
            ]);
        }

        return $this->apiClient->getPhoneNumberInfo($this->getCredentials());
    }
}
