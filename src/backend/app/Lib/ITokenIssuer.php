<?php

namespace App\Lib;

use App\DTO\TransactionDataContainer;
use App\Enums\VendingCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Models\Device;

/**
 * Issues a device's tokens. Core asks the plugin a device is vended by
 * (devices.vending_plugin_id), which is its manufacturer's plugin unless a user
 * moved the device to another vending plugin, so an implementation never
 * assumes it made the devices it is handed.
 */
interface ITokenIssuer {
    /**
     * Which of the calls below this issuer can actually serve. The others throw,
     * and a reset cannot be attempted just to find out whether it works, so callers
     * read this to decide what to offer rather than discovering it from a failure.
     *
     * @return list<VendingCapability>
     */
    public function capabilities(): array;

    /**
     * {@see VendingCapability::AddCredit}.
     *
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function addCredit(TransactionDataContainer $transactionContainer): array;

    /**
     * {@see VendingCapability::UnlockPaygo}.
     *
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function unlockPaygo(TransactionDataContainer $transactionContainer): array;

    /**
     * {@see VendingCapability::ResetCredit}.
     *
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function resetCredit(Device $device): array;

    /**
     * {@see VendingCapability::ClearCredit}.
     *
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearCredit(Device $device): array;
}
