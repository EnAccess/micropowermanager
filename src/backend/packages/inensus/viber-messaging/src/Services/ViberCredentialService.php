<?php

namespace Inensus\ViberMessaging\Services;

use App\Traits\EncryptsCredentials;
use Inensus\ViberMessaging\Exceptions\WebhookNotCreatedException;
use Inensus\ViberMessaging\Models\ViberCredential;

class ViberCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private ViberCredential $credential,
        private WebhookService $webhookService,
        private AccountService $accountService,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_token' => null,
            'webhook_url' => null,
            'has_webhook_created' => false,
            'deep_link' => null,
        ]);
    }

    public function getCredentials() {
        $credential = $this->credential->newQuery()->first();

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->api_token) {
                $credential->api_token = $this->decryptCredentialField($credential->api_token);
            }
            if ($credential->webhook_url) {
                $credential->webhook_url = $this->decryptCredentialField($credential->webhook_url);
            }
            if ($credential->deep_link) {
                $credential->deep_link = $this->decryptCredentialField($credential->deep_link);
            }
        }

        return $credential;
    }

    /**
     * @throws WebhookNotCreatedException
     */
    public function updateCredentials(array $data) {
        $credential = $this->credential->newQuery()->find($data['id']);

        $encryptedData = $this->encryptCredentialFields($data, ['api_token', 'webhook_url']);
        $credential->update($encryptedData);
        $credential->save();

        if (!$credential->has_webhook_created) {
            $this->webhookService->createWebHook($credential);
        }

        if (!$credential->deep_link) {
            $uri = $this->accountService->getAccountInfo($credential);
            $credential->deep_link = "viber://pa?chatURI=$uri&text=register+change_this_with_your_meter_serial_number";
            $credential->save();
        }

        return $credential->fresh();
    }
}
