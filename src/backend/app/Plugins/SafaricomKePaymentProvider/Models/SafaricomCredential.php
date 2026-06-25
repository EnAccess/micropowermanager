<?php

namespace App\Plugins\SafaricomKePaymentProvider\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $consumer_key
 * @property string      $consumer_secret
 * @property string      $passkey
 * @property string      $shortcode
 * @property string      $environment
 * @property string|null $validation_url
 * @property string|null $confirmation_url
 * @property string|null $timeout_url
 * @property string|null $result_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SafaricomCredential extends BaseModel {
    protected $table = 'safaricom_credentials';

    /**
     * Shortcode used for actual Daraja calls. Falls back to the well-known
     * sandbox shortcode (174379) when the operator left it blank in sandbox
     * mode, so they don't have to provide one just to run a test transaction.
     * Production must always carry an explicit shortcode.
     */
    public function getEffectiveShortcode(): string {
        if (!empty($this->shortcode)) {
            return $this->shortcode;
        }

        return $this->isSandbox()
            ? (string) config('safaricom-ke-payment-provider.sandbox.shortcode', '174379')
            : '';
    }

    /**
     * Passkey used for actual Daraja calls. Mirrors getEffectiveShortcode:
     * sandbox falls back to the public LNM test passkey, production must
     * always carry an explicit one.
     */
    public function getEffectivePasskey(): string {
        if (!empty($this->passkey)) {
            return $this->passkey;
        }

        return $this->isSandbox()
            ? (string) config('safaricom-ke-payment-provider.sandbox.passkey', '')
            : '';
    }

    public function isProduction(): bool {
        return $this->environment === 'production';
    }

    public function isSandbox(): bool {
        return ($this->environment ?? 'sandbox') === 'sandbox';
    }
}
