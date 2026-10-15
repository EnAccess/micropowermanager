<?php

namespace App\Plugins\OpenPaygo\Services;

use App\Models\Device;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoDeviceConfigurationException;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoSecretStorageException;
use App\Plugins\OpenPaygo\Models\OpenPaygoDeviceConfiguration;
use App\Plugins\OpenPaygo\Models\OpenPaygoIssuanceReservation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OpenPaygoDeviceConfigurationService {
    /**
     * @return array{secretKeyHex: string, startingCode: int, timeDivider: int|null, nextCounter: int}
     */
    public function getForDevice(Device $device): array {
        $configuration = $this->findForDevice($device);

        return [
            'secretKeyHex' => $this->decryptSecret($configuration->secret_key_hex),
            'startingCode' => (int) $configuration->starting_code,
            'timeDivider' => $configuration->time_divider === null ? null : (int) $configuration->time_divider,
            'nextCounter' => (int) $configuration->next_counter,
        ];
    }

    public function saveForDevice(
        Device $device,
        mixed $secretKeyHex,
        mixed $startingCode,
        mixed $nextCounter = null,
        mixed $timeDivider = null,
    ): OpenPaygoDeviceConfiguration {
        $this->validateSecret($secretKeyHex);
        $this->validateStartingCode($startingCode);

        if ($nextCounter !== null) {
            $this->validateCounter($nextCounter);
        }
        if ($timeDivider !== null) {
            $this->validateTimeDivider($timeDivider);
        }

        return DB::connection('tenant')->transaction(function () use ($device, $secretKeyHex, $startingCode, $nextCounter, $timeDivider): OpenPaygoDeviceConfiguration {
            $lockedDevice = Device::query()->whereKey($device->getKey())->lockForUpdate()->first();
            if ($lockedDevice === null) {
                throw new OpenPaygoDeviceConfigurationException('OpenPAYGO configuration requires a persisted device.');
            }

            // Issuance takes the same device lock before allocating its immutable snapshot.
            if (OpenPaygoIssuanceReservation::query()
                ->where('device_id', $lockedDevice->getKey())
                ->where('state', '!=', OpenPaygoIssuanceReservation::STATE_COMPLETED)
                ->where(function ($query): void {
                    $query->whereNotNull('active_device_id')->orWhereNotNull('counter');
                })->exists()) {
                throw new OpenPaygoDeviceConfigurationException('OpenPAYGO configuration cannot change while token issuance is unresolved.');
            }

            $configuration = OpenPaygoDeviceConfiguration::query()
                ->where('device_id', $lockedDevice->getKey())->lockForUpdate()->first()
                ?? new OpenPaygoDeviceConfiguration(['device_id' => $lockedDevice->getKey()]);
            if (!$configuration->exists && ($lockedDevice->tokens()->exists()
                || OpenPaygoIssuanceReservation::query()->where('device_id', $lockedDevice->getKey())->whereNotNull('counter')->exists())) {
                throw new OpenPaygoDeviceConfigurationException('OpenPAYGO provisioning cannot replace missing configuration for a device with issued tokens; restore its configuration and counter through recovery.');
            }
            if (!$configuration->exists && $nextCounter === null) {
                throw new OpenPaygoDeviceConfigurationException('An initial OpenPAYGO counter is required.');
            }
            if ($configuration->exists && $nextCounter !== null && $nextCounter !== (int) $configuration->next_counter) {
                throw new OpenPaygoDeviceConfigurationException('The OpenPAYGO counter is managed by token issuance and cannot be changed through provisioning.');
            }

            try {
                $encryptedSecret = Crypt::encryptString($secretKeyHex);
            } catch (\Throwable) {
                throw new OpenPaygoSecretStorageException('OpenPAYGO secret could not be encrypted.');
            }

            $configuration->secret_key_hex = $encryptedSecret;
            $configuration->starting_code = $startingCode;
            if ($timeDivider !== null) {
                $configuration->time_divider = $timeDivider;
            }
            if ($nextCounter !== null) {
                $configuration->next_counter = $nextCounter;
            }
            $configuration->save();

            return $configuration;
        });
    }

    private function findForDevice(Device $device): OpenPaygoDeviceConfiguration {
        $configuration = OpenPaygoDeviceConfiguration::query()->where('device_id', $device->getKey())->first();
        if ($configuration === null) {
            throw new OpenPaygoDeviceConfigurationException('OpenPAYGO configuration is missing for this device.');
        }

        return $configuration;
    }

    private function decryptSecret(string $encryptedSecret): string {
        try {
            return Crypt::decryptString($encryptedSecret);
        } catch (\Throwable) {
            throw new OpenPaygoSecretStorageException('OpenPAYGO secret could not be decrypted.');
        }
    }

    private function validateSecret(mixed $secretKeyHex): void {
        if (!is_string($secretKeyHex) || preg_match('/\A[0-9a-fA-F]{32}\z/', $secretKeyHex) !== 1) {
            throw new OpenPaygoDeviceConfigurationException('OpenPAYGO secret must be 32 hexadecimal characters.');
        }
    }

    private function validateStartingCode(mixed $startingCode): void {
        if (!is_int($startingCode) || $startingCode < 0 || $startingCode > 999999999) {
            throw new OpenPaygoDeviceConfigurationException('OpenPAYGO starting code must be an integer from 0 to 999999999.');
        }
    }

    private function validateCounter(mixed $counter): void {
        if (!is_int($counter) || $counter < 0 || $counter > 100000) {
            throw new OpenPaygoDeviceConfigurationException('OpenPAYGO counter must be an integer from 0 to 100000.');
        }
    }

    private function validateTimeDivider(mixed $timeDivider): void {
        if (!is_int($timeDivider) || $timeDivider < 1 || $timeDivider > 255) {
            throw new OpenPaygoDeviceConfigurationException('OpenPAYGO time divider must be an integer from 1 to 255.');
        }
    }
}
