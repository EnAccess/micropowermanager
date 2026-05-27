<?php

namespace App\Plugins\PesapalPaymentProvider\Services;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Traits\EncryptsCredentials;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Carbon;

class PesapalCredentialService {
    use EncryptsCredentials;

    private const ENCRYPTED_FIELDS = ['consumer_key', 'consumer_secret'];

    public function __construct(
        private PesapalCredential $pesapalCredential,
        private Container $container,
    ) {}

    public function getCredentials(): PesapalCredential {
        $credential = $this->pesapalCredential->newQuery()->first();

        if (!$credential) {
            return $this->createCredentials();
        }

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    public function createCredentials(): PesapalCredential {
        return $this->pesapalCredential->newQuery()->create([
            'consumer_key' => '',
            'consumer_secret' => '',
            'callback_url' => '',
            'merchant_name' => 'Pesapal',
            'merchant_email' => null,
            'environment' => 'test',
            'currency' => config('pesapal-payment-provider.currency.default', 'KES'),
            'ipn_id' => null,
            'ipn_registered_at' => null,
        ]);
    }

    public function hasCredentials(): bool {
        return $this->pesapalCredential->newQuery()->exists();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \RuntimeException if the credential ends up without consumer key/secret,
     *                           or if PesaPal IPN registration fails
     */
    public function updateCredentials(array $data): PesapalCredential {
        $credential = $this->getCredentials();
        $tokenService = $this->container->make(PesapalTokenService::class);

        // Caller is expected to drop blank consumer_key/secret from $data so the
        // stored ciphertext is preserved. Anything still in $data is a real change.
        $secretsRotated = array_key_exists('consumer_key', $data)
            || array_key_exists('consumer_secret', $data);
        $environmentChanged = array_key_exists('environment', $data)
            && $data['environment'] !== $credential->getEnvironment();

        $credential->update($this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS));
        $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);

        if ($credential->getConsumerKey() === '' || $credential->getConsumerSecret() === '') {
            throw new \RuntimeException('Consumer key and consumer secret are required.');
        }

        if ($secretsRotated || $environmentChanged) {
            $tokenService->forget($credential);
        }

        $this->ensureIpnRegistered($credential);

        return $credential;
    }

    /**
     * Re-registers the IPN whenever credentials change or there is no stored ipn_id.
     * Surfaces failures to the caller; the controller is expected to translate to a 422.
     */
    public function ensureIpnRegistered(PesapalCredential $credential): void {
        $callbackUrl = $credential->getCallbackUrl();
        if (in_array($callbackUrl, [null, '', '0'], true)) {
            return;
        }

        // The IPN URL must be a server-reachable endpoint, distinct from the
        // browser-facing callback URL that returns the customer to the result page.
        $ipnUrl = $this->buildIpnUrl();
        if ($ipnUrl === null) {
            return;
        }

        $apiService = $this->container->make(PesapalApiService::class);
        $result = $apiService->registerIpn($credential, $ipnUrl);
        if ($result['error'] !== null) {
            throw new \RuntimeException('PesaPal IPN registration failed: '.$result['error']);
        }

        $credential->update([
            'ipn_id' => $result['ipn_id'],
            'ipn_registered_at' => Carbon::now(),
        ]);
        $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    private function buildIpnUrl(): ?string {
        $companyId = request()->attributes->get('companyId');
        if (!is_int($companyId)) {
            return null;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '') {
            return null;
        }

        return $appUrl.'/api/pesapal/ipn/'.$companyId;
    }
}
