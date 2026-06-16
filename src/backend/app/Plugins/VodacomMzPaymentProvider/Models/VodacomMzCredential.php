<?php

namespace App\Plugins\VodacomMzPaymentProvider\Models;

use App\Models\Base\BaseModel;
use App\Plugins\VodacomMzPaymentProvider\Exceptions\VodacomMzApiResponseException;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $api_key
 * @property string|null $public_key
 * @property string|null $service_provider_code
 * @property bool        $live
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VodacomMzCredential extends BaseModel {
    private const string HOST_LIVE = 'api.vm.co.mz';
    private const string HOST_SANDBOX = 'api.sandbox.vm.co.mz';

    protected $table = 'vodacom_mz_credentials';

    /** @var array<string, string> */
    protected $casts = [
        'live' => 'boolean',
    ];

    /**
     * Build a full IPG endpoint URL. Only the host changes between environments; the
     * ports observed so far are identical for sandbox and live.
     */
    public function buildUri(int $port, string $path): string {
        $host = $this->live ? self::HOST_LIVE : self::HOST_SANDBOX;

        return 'https://'.$host.':'.$port.$path;
    }

    /**
     * Build the IPG Authorization bearer: the API key RSA-encrypted with the provider
     * public key (PKCS#1 v1.5) and base64-encoded. The public key is stored as bare
     * base64, so it is wrapped in a PEM envelope before use.
     */
    public function getBearerToken(): string {
        $pemPublicKey = "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split((string) $this->public_key, 64, "\n")
            ."-----END PUBLIC KEY-----\n";

        $publicKeyResource = openssl_pkey_get_public($pemPublicKey);

        if ($publicKeyResource === false) {
            throw new VodacomMzApiResponseException('Invalid Vodacom MZ public key');
        }

        $encrypted = '';
        if (!openssl_public_encrypt((string) $this->api_key, $encrypted, $publicKeyResource, OPENSSL_PKCS1_PADDING)) {
            throw new VodacomMzApiResponseException('Failed to encrypt Vodacom MZ API key');
        }

        return base64_encode($encrypted);
    }
}
