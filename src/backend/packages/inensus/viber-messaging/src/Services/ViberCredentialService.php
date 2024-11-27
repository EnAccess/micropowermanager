<?php

namespace Inensus\ViberMessaging\Services;

use Inensus\ViberMessaging\Models\ViberCredential;

class ViberCredentialService {
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
        return $this->credential->newQuery()->first();
    }

    /**
     * @throws \Inensus\ViberMessaging\Exceptions\WebhookNotCreatedException
     */
    public function updateCredentials($data) {
        $credential = $this->credential->newQuery()->find($data['id']);

        $credential->update([
            'api_token' => $data['api_token'],
            'webhook_url' => $data['webhook_url'],
        ]);
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
