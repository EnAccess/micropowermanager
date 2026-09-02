<?php

namespace App\Lib;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Models\Device;

interface IManufacturerAPI {
    /**
     * Which of the calls below this manufacturer can actually serve. The others throw,
     * and a reset cannot be attempted just to find out whether it works, so callers
     * read this to decide what to offer rather than discovering it from a failure.
     *
     * @return list<ManufacturerCapability>
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
     * days already paid for and can be resold under a new payment plan. Returns the
     * token data to persist, in the shape the other two calls return.
     *
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array;
}
