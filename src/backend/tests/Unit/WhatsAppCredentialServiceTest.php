<?php

namespace Tests\Unit;

use App\Plugins\WhatsApp\Exceptions\WhatsAppApiException;
use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\WhatsAppTestCase;

class WhatsAppCredentialServiceTest extends WhatsAppTestCase {
    private WhatsAppCredentialService $service;

    protected function setUp(): void {
        parent::setUp();

        Http::preventStrayRequests();
        $this->service = resolve(WhatsAppCredentialService::class);
    }

    public function testReadingMissingCredentialsCreatesOneDisabledDefault(): void {
        $credential = $this->service->getCredentials();
        $again = $this->service->getCredentials();

        $this->assertSame($credential->id, $again->id);
        $this->assertSame(1, WhatsAppCredential::query()->count());
        $this->assertFalse($credential->is_enabled);
        $this->assertSame('v21.0', $credential->api_version);
        foreach (array_keys($this->secrets()) as $field) {
            $this->assertNull($credential->getAttribute($field));
        }
    }

    public function testAllSecretsUseLaravelEncryptionAndDecryptOnRead(): void {
        $updated = $this->service->updateCredentials($this->secrets());
        $stored = WhatsAppCredential::query()->findOrFail($updated->id);
        $retrieved = $this->service->getCredentials();

        foreach ($this->secrets() as $field => $plaintext) {
            $ciphertext = $stored->getRawOriginal($field);
            $this->assertNotSame($plaintext, $ciphertext);
            $this->assertSame($plaintext, Crypt::decryptString($ciphertext));
            $this->assertSame($plaintext, $updated->getAttribute($field));
            $this->assertSame($plaintext, $retrieved->getAttribute($field));
        }
    }

    public function testOmittedSecretsPreserveTheirCiphertext(): void {
        $this->service->updateCredentials($this->secrets());
        $before = WhatsAppCredential::query()->firstOrFail()->getAttributes();

        $updated = $this->service->updateCredentials(['sender_phone_number' => '+250788123456']);
        $stored = WhatsAppCredential::query()->firstOrFail();

        $this->assertSame('+250788123456', $updated->sender_phone_number);
        foreach ($this->secrets() as $field => $plaintext) {
            $this->assertSame($before[$field], $stored->getRawOriginal($field));
            $this->assertSame($plaintext, $updated->getAttribute($field));
        }
    }

    public function testSecretsCanBeReplacedAndExplicitlyCleared(): void {
        $this->service->updateCredentials($this->secrets());
        $replacement = array_map(fn (string $secret): string => 'replacement-'.$secret, $this->secrets());
        $updated = $this->service->updateCredentials($replacement);
        $stored = WhatsAppCredential::query()->firstOrFail();

        foreach ($replacement as $field => $plaintext) {
            $this->assertSame($plaintext, $updated->getAttribute($field));
            $this->assertSame($plaintext, Crypt::decryptString($stored->getRawOriginal($field)));
        }

        $cleared = $this->service->updateCredentials(array_fill_keys(array_keys($this->secrets()), null));
        $stored = WhatsAppCredential::query()->firstOrFail();
        foreach (array_keys($this->secrets()) as $field) {
            $this->assertNull($cleared->getAttribute($field));
            $this->assertNull($stored->getRawOriginal($field));
        }
    }

    public function testSecretsAreHiddenFromStoredAndDecryptedModelSerialization(): void {
        $updated = $this->service->updateCredentials($this->secrets());

        foreach ([$updated, $this->service->getCredentials(), WhatsAppCredential::query()->firstOrFail()] as $credential) {
            $serialized = json_decode($credential->toJson(), true, flags: JSON_THROW_ON_ERROR);
            foreach (array_keys($this->secrets()) as $field) {
                $this->assertArrayNotHasKey($field, $credential->toArray());
                $this->assertArrayNotHasKey($field, $serialized);
            }
        }
    }

    public function testSavingReturnedModelsDoesNotPersistDecryptedSecrets(): void {
        $this->service->updateCredentials($this->secrets());
        $before = WhatsAppCredential::query()->firstOrFail()->getAttributes();
        $returned = $this->service->getCredentials();
        $returned->sender_phone_number = '+250788123456';
        $returned->save();
        foreach (array_keys($this->secrets()) as $field) {
            $this->assertSame($before[$field], WhatsAppCredential::query()->firstOrFail()->getRawOriginal($field));
        }
    }

    public function testEmptyAndMaskedSecretsRejectTheWholeUpdate(): void {
        $this->service->updateCredentials($this->secrets());
        $before = WhatsAppCredential::query()->firstOrFail()->getAttributes();

        foreach (array_keys($this->secrets()) as $field) {
            foreach (['', '   ', '••••••••', '********'] as $invalidSecret) {
                try {
                    $this->service->updateCredentials([
                        'sender_phone_number' => '+250788999999',
                        $field => $invalidSecret,
                    ]);
                    $this->fail('Expected invalid secret to be rejected.');
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey($field, $exception->errors());
                }

                $this->assertSame($before, WhatsAppCredential::query()->firstOrFail()->getAttributes());
            }
        }
    }

    public function testCorruptSecretsFailWithoutExposingTheirContents(): void {
        foreach (array_keys($this->secrets()) as $field) {
            $this->service->updateCredentials($this->secrets());
            $stored = WhatsAppCredential::query()->firstOrFail();
            $stored->setAttribute($field, 'corrupt-sensitive-ciphertext');
            $stored->save();

            try {
                $this->service->getCredentials();
                $this->fail('Expected corrupt ciphertext to fail closed.');
            } catch (WhatsAppApiException $exception) {
                $this->assertStringNotContainsString('corrupt-sensitive-ciphertext', $exception->getMessage());
                foreach ($this->secrets() as $plaintext) {
                    $this->assertStringNotContainsString($plaintext, $exception->getMessage());
                }
            }
        }
    }

    public function testEncryptionFailureDoesNotPartiallyUpdateExistingCredentials(): void {
        $this->service->updateCredentials($this->secrets());
        $before = WhatsAppCredential::query()->firstOrFail()->getAttributes();
        $this->failSecondEncryption();

        try {
            $this->service->updateCredentials($this->secrets() + ['sender_phone_number' => '+250788999999']);
            $this->fail('Expected encryption failure.');
        } catch (WhatsAppApiException|EncryptException) {
            $this->assertSame($before, WhatsAppCredential::query()->firstOrFail()->getAttributes());
        }
    }

    public function testEncryptionFailureDoesNotCreateAnIncompleteCredential(): void {
        $this->failSecondEncryption();

        try {
            $this->service->updateCredentials($this->secrets());
            $this->fail('Expected encryption failure.');
        } catch (WhatsAppApiException|EncryptException) {
            $this->assertSame(0, WhatsAppCredential::query()->count());
        }
    }

    private function failSecondEncryption(): void {
        $attempts = 0;
        Crypt::shouldReceive('encryptString')->andReturnUsing(function (string $plaintext) use (&$attempts): string {
            if (++$attempts === 2) {
                throw new EncryptException('Encryption unavailable.');
            }

            return 'pending-ciphertext';
        });
    }

    /** @return array<string, string> */
    private function secrets(): array {
        return [
            'api_access_token' => 'test-access-token',
            'webhook_verify_token' => 'test-verify-token',
            'meta_app_secret' => 'test-app-secret',
        ];
    }
}
