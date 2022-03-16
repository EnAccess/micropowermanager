<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 07.06.18
 * Time: 11:28
 */

namespace App\Lib;

use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterToken;

interface IManufacturerAPI
{
    /**
     * @param  TransactionDataContainer $transactionContainer
     * @return array
     */
    public function chargeMeter(TransactionDataContainer $transactionContainer): array;

    public function clearMeter(Meter $meter);

    public function associateManufacturerTransaction(TransactionDataContainer $transactionContainer);
}
