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
     * @return array<string, mixed>
     */
    public function chargeDevice(TransactionDataContainer $transactionContainer): array;

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function unlockDevice(TransactionDataContainer $transactionContainer): array;

    /**
     * Clears the device's remaining credit, so a repossessed unit stops running on
     * credit already paid for. It vends whichever of {@see VendingCapability::Reset}
     * and {@see VendingCapability::ClearCredit} the issuer declares. Returns the
     * token data to persist, in the shape the other two calls return.
     *
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array;
}
