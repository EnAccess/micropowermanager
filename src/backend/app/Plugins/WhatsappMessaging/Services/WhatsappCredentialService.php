<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Traits\EncryptsCredentials;

class WhatsappCredentialService {
    use EncryptsCredentials;

    private const array ENCRYPTED_FIELDS = ['access_token', 'app_secret', 'webhook_verify_token'];

    public function __construct(
        private WhatsappCredential $credential,
    ) {}

    public function getCredentials(): ?WhatsappCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }
}
