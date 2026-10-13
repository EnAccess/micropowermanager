<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Traits\EncryptsCredentials;
use Illuminate\Support\Str;

class WhatsappCredentialService {
    use EncryptsCredentials;

    // Encrypted at rest; an empty value on update keeps the stored one, so the UI never has to resend (or even know) them.
    private const array SECRET_FIELDS = ['access_token', 'app_secret', 'webhook_verify_token'];

    public function __construct(private WhatsappCredential $credential, private WhatsappApiClient $apiClient) {}

    public function createCredentials(): WhatsappCredential {
        // Matched on "any row" rather than id 1: BaseModel guards id, so the created row's id is not guaranteed to be 1.
        return $this->credential->newQuery()->firstOrCreate([], ['webhook_verify_token' => $this->encryptCredentialField(Str::random(40))]);
    }

    public function getCredentials(): ?WhatsappCredential {
        return $this->decryptCredentialFields($this->credential->newQuery()->first(), self::SECRET_FIELDS);
    }

    /**
     * Nothing is saved unless Meta accepts the new values; its answer also gives the display number used for the opt-in link.
     *
     * @param array<string, string> $data
     */
    public function updateCredentials(array $data): WhatsappCredential {
        $credential = $this->credential->newQuery()->firstOrFail();
        $data = array_filter($data, fn (string $value, string $field): bool => $value !== '' || !in_array($field, self::SECRET_FIELDS, true), ARRAY_FILTER_USE_BOTH);

        $candidate = $credential->replicate();
        $this->decryptCredentialFields($candidate, self::SECRET_FIELDS);
        $candidate->fill($data);
        $data['display_phone_number'] = (string) $this->apiClient->get($candidate, (string) $candidate->phone_number_id, ['fields' => 'display_phone_number'])->json('display_phone_number');

        $credential->update($this->encryptCredentialFields($data, self::SECRET_FIELDS));

        return $this->decryptCredentialFields($credential->fresh(), self::SECRET_FIELDS);
    }

    public function isConfigured(): bool {
        $credential = $this->credential->newQuery()->first();

        return !empty($credential->access_token) && !empty($credential->phone_number_id) && !empty($credential->app_secret);
    }
}
