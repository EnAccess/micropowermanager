<?php

namespace App\Lib;

use App\Misc\TransactionDataContainer;
use App\Models\Device;

interface IManufacturerAPI {
    /**
     * @param TransactionDataContainer $transactionContainer
     *
     * @return array<string, mixed>
     */
    public function chargeDevice(TransactionDataContainer $transactionContainer): array;

    public function clearDevice(Device $device): void;
}
