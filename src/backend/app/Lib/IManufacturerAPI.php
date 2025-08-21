<?php

namespace App\Lib;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Misc\TransactionDataContainer;
use App\Models\Device;

interface IManufacturerAPI {
    /**
     * @param TransactionDataContainer $transactionContainer
     *
     * @return array<string, mixed>
     */
    public function chargeDevice(TransactionDataContainer $transactionContainer): array;

    /**
     * @param Device $device
     *
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array;
}
