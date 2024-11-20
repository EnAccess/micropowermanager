<?php

namespace App\Lib;

use App\Misc\TransactionDataContainer;
use App\Models\Device;

interface IManufacturerAPI
{
    public function chargeDevice(TransactionDataContainer $transactionContainer): array;

    public function clearDevice(Device $device);
}
