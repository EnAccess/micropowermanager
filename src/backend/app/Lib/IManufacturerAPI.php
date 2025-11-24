<?php

namespace App\Lib;

use App\DTO\TransactionDataContainer;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Models\Device;

interface IManufacturerAPI {
    /**
     * @return array<string, mixed>
     */
    public function chargeDevice(TransactionDataContainer $transactionContainer): array;

    /**
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array;
}
