<?php

namespace App\Plugins\FlutterwavePaymentProvider\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int         $id
 * @property string      $secret_key
 * @property string      $public_key
 * @property string      $encryption_key
 * @property string|null $webhook_secret_hash
 * @property string|null $callback_url
 * @property string      $merchant_name
 * @property string|null $merchant_email
 * @property string      $environment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FlutterwaveCredential extends BaseModel {
    protected $table = 'flutterwave_credentials';

    public function getSecretKey(): string {
        try {
            return Crypt::decrypt($this->attributes['secret_key']);
        } catch (\Throwable) {
            return $this->attributes['secret_key'] ?? '';
        }
    }

    public function getPublicKey(): string {
        try {
            return Crypt::decrypt($this->attributes['public_key']);
        } catch (\Throwable) {
            return $this->attributes['public_key'] ?? '';
        }
    }

    public function getEncryptionKey(): string {
        try {
            return Crypt::decrypt($this->attributes['encryption_key']);
        } catch (\Throwable) {
            return $this->attributes['encryption_key'] ?? '';
        }
    }

    /**
     * The value set in Flutterwave's dashboard under Settings → Webhooks →
     * Secret Hash — distinct from the API secret key. Flutterwave only signs
     * webhook payloads (via the `flutterwave-signature` header) when this is
     * configured on their end; it must match what's stored here.
     */
    public function getWebhookSecretHash(): string {
        try {
            return Crypt::decrypt($this->attributes['webhook_secret_hash']);
        } catch (\Throwable) {
            return $this->attributes['webhook_secret_hash'] ?? '';
        }
    }

    public function isLive(): bool {
        return $this->environment === 'live';
    }

    public function isTest(): bool {
        return $this->environment === 'test';
    }
}
