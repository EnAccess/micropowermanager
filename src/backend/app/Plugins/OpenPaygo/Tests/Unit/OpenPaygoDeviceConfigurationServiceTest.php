<?php

declare(strict_types=1);

namespace App\Plugins\OpenPaygo\Tests\Unit;

use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoDeviceConfigurationException;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoSecretStorageException;
use App\Plugins\OpenPaygo\Models\OpenPaygoDeviceConfiguration;
use App\Plugins\OpenPaygo\Models\OpenPaygoIssuanceReservation;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class OpenPaygoDeviceConfigurationServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    public function testSecretIsEncryptedAtRestAndDecryptedForInternalUse(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $secret = '0123456789abcdef0123456789abcdef';

        $configuration = $service->saveForDevice($device, $secret, 0, 0, 4);

        $stored = OpenPaygoDeviceConfiguration::query()->findOrFail($configuration->id);
        $this->assertNotSame($secret, $stored->getRawOriginal('secret_key_hex'));
        $this->assertSame($secret, $service->getForDevice($device)['secretKeyHex']);
        $this->assertSame(4, $service->getForDevice($device)['timeDivider']);
    }

    public function testEncryptionFailureDoesNotPersistPlaintextSecret(): void {
        $device = $this->createDevice();
        Crypt::shouldReceive('encryptString')->once()->andThrow(new \RuntimeException('sensitive failure'));

        try {
            new OpenPaygoDeviceConfigurationService()->saveForDevice(
                $device,
                '0123456789abcdef0123456789abcdef',
                1,
                0,
            );
            $this->fail('Expected encryption failure.');
        } catch (OpenPaygoSecretStorageException $exception) {
            $this->assertSame('OpenPAYGO secret could not be encrypted.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('openpaygo_device_configurations', ['device_id' => $device->id], 'tenant');
    }

    public function testDecryptionFailureDoesNotReturnPlaintextOrUnderlyingError(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $service->saveForDevice($device, '0123456789abcdef0123456789abcdef', 1, 0);
        Crypt::shouldReceive('decryptString')->once()->andThrow(new \RuntimeException('secret leaked by crypto'));

        try {
            $service->getForDevice($device);
            $this->fail('Expected decryption failure.');
        } catch (OpenPaygoSecretStorageException $exception) {
            $this->assertSame('OpenPAYGO secret could not be decrypted.', $exception->getMessage());
            $this->assertStringNotContainsString('secret leaked', $exception->getMessage());
        }
    }

    public function testDeviceCanHaveOnlyOneConfiguration(): void {
        $device = $this->createDevice();
        $attributes = [
            'device_id' => $device->id,
            'secret_key_hex' => Crypt::encryptString('0123456789abcdef0123456789abcdef'),
            'starting_code' => 1,
            'next_counter' => 0,
        ];
        OpenPaygoDeviceConfiguration::query()->create($attributes);

        $this->expectException(QueryException::class);
        OpenPaygoDeviceConfiguration::query()->create($attributes);
    }

    public function testStartingCodeAndCounterRangesAreValidated(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $secret = '0123456789abcdef0123456789abcdef';

        $service->saveForDevice($this->createDevice(), $secret, 0, 0);
        $service->saveForDevice($device, $secret, 999999999, 100000);
        $this->assertSame(999999999, $service->getForDevice($device)['startingCode']);
        $this->assertSame(100000, $service->getForDevice($device)['nextCounter']);

        foreach ([[-1, 0], [1000000000, 0], ['1', 0], [1, -1], [1, 100001], [1, '2']] as [$startingCode, $counter]) {
            try {
                $service->saveForDevice($device, $secret, $startingCode, $counter);
                $this->fail('Expected range validation failure.');
            } catch (OpenPaygoDeviceConfigurationException) {
            }
        }
    }

    public function testTimeDividerMustBeAnIntegerBetweenOneAnd255(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $secret = '0123456789abcdef0123456789abcdef';

        foreach ([0, 256, -1, '4'] as $timeDivider) {
            try {
                $service->saveForDevice($device, $secret, 1, 0, $timeDivider);
                $this->fail('Expected time-divider validation failure.');
            } catch (OpenPaygoDeviceConfigurationException) {
            }
        }

        $service->saveForDevice($device, $secret, 1, 0, 1);
        $service->saveForDevice($device, $secret, 1, 0, 255);

        $this->assertSame(255, $service->getForDevice($device)['timeDivider']);
    }

    public function testOrdinaryConfigurationUpdatePreservesCounterWhenOmitted(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $service->saveForDevice($device, '0123456789abcdef0123456789abcdef', 1, 17);

        $service->saveForDevice($device, 'fedcba9876543210fedcba9876543210', 2);

        $this->assertSame(17, $service->getForDevice($device)['nextCounter']);
        $this->assertSame(2, $service->getForDevice($device)['startingCode']);
    }

    public function testMissingConfigurationFailsClearly(): void {
        $device = $this->createDevice();

        $this->expectExceptionMessage('OpenPAYGO configuration is missing for this device.');
        new OpenPaygoDeviceConfigurationService()->getForDevice($device);
    }

    public function testProvisioningCannotResetOrAdvanceAnExistingCounter(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $secret = '0123456789abcdef0123456789abcdef';
        $service->saveForDevice($device, $secret, 728, 17, 1);

        foreach ([0, 18] as $counter) {
            try {
                $service->saveForDevice($device, $secret, 728, $counter, 1);
                $this->fail('Provisioning must not change a managed counter.');
            } catch (OpenPaygoDeviceConfigurationException $exception) {
                $this->assertStringContainsString('counter is managed by token issuance', $exception->getMessage());
            }
        }
        $this->assertSame(17, $service->getForDevice($device)['nextCounter']);
    }

    public function testConfigurationCannotChangeWhileAnIssuanceSnapshotIsUnresolved(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $secret = '0123456789abcdef0123456789abcdef';
        $configuration = $service->saveForDevice($device, $secret, 728, 0, 1);
        $reservation = $this->createReservation($device);
        $reservation->active_device_id = $device->id;
        $reservation->counter = 0;

        foreach ([
            OpenPaygoIssuanceReservation::STATE_RESERVED,
            OpenPaygoIssuanceReservation::STATE_REQUESTING,
            OpenPaygoIssuanceReservation::STATE_UNCERTAIN,
            OpenPaygoIssuanceReservation::STATE_GENERATED,
        ] as $state) {
            $reservation->state = $state;
            $reservation->save();
            try {
                $service->saveForDevice($device, $secret, 729, null, 4);
                $this->fail('Unresolved issuance must keep its configuration unchanged.');
            } catch (OpenPaygoDeviceConfigurationException $exception) {
                $this->assertStringContainsString('issuance is unresolved', $exception->getMessage());
            }
            $this->assertSame(728, $configuration->fresh()->starting_code);
            $this->assertSame(1, $configuration->fresh()->time_divider);
        }
    }

    public function testBlockedPaymentWithoutAnAllocatedCounterAllowsConfigurationCorrection(): void {
        $device = $this->createDevice();
        $service = new OpenPaygoDeviceConfigurationService();
        $secret = '0123456789abcdef0123456789abcdef';
        $service->saveForDevice($device, $secret, 728, 7);
        $reservation = $this->createReservation($device);

        $service->saveForDevice($device, $secret, 728, null, 1);

        $this->assertSame(1, $service->getForDevice($device)['timeDivider']);
        $this->assertSame(7, $service->getForDevice($device)['nextCounter']);
        $this->assertNull($reservation->fresh()->counter);
    }

    public function testMissingConfigurationCannotBeRecreatedAfterTokenIssuance(): void {
        foreach ([true, false] as $hasToken) {
            $device = $this->createDevice();
            $reservation = $this->createReservation($device);
            $reservation->state = OpenPaygoIssuanceReservation::STATE_COMPLETED;
            $reservation->counter = 7;
            $reservation->next_counter = 9;
            $reservation->save();
            if ($hasToken) {
                Token::query()->create([
                    'device_id' => $device->id,
                    'transaction_id' => $reservation->transaction_id,
                    'token' => '123456789',
                    'token_type' => Token::TYPE_TIME,
                    'token_unit' => Token::UNIT_DAYS,
                    'token_amount' => 1,
                ]);
                $reservation->delete();
            }

            try {
                new OpenPaygoDeviceConfigurationService()->saveForDevice($device, '0123456789abcdef0123456789abcdef', 728, 0, 1);
                $this->fail('An issued device must not receive a replacement initial counter.');
            } catch (OpenPaygoDeviceConfigurationException $exception) {
                $this->assertStringContainsString('restore its configuration and counter', $exception->getMessage());
            }
            $this->assertDatabaseMissing('openpaygo_device_configurations', ['device_id' => $device->id], 'tenant');
        }
    }

    private function createReservation(Device $device): OpenPaygoIssuanceReservation {
        $transaction = Transaction::query()->create([
            'original_transaction_id' => 1,
            'original_transaction_type' => CashTransaction::class,
            'amount' => 10,
            'sender' => 'test',
            'message' => $device->device_serial,
            'type' => Transaction::TYPE_AD_HOC,
        ]);

        return OpenPaygoIssuanceReservation::query()->create([
            'device_id' => $device->id,
            'transaction_id' => $transaction->id,
            'operation' => 'credit',
            'generator_operation' => 'ADD_TIME',
            'credit_days' => 1,
            'state' => OpenPaygoIssuanceReservation::STATE_BLOCKED,
        ]);
    }

    private function createDevice(): Device {
        return Device::query()->create([
            'person_id' => null,
            'device_type' => 'meter',
            'device_id' => 1,
            'device_serial' => uniqid('openpaygo-', true),
        ]);
    }
}
