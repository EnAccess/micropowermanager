<?php

namespace App\Misc;

use App\Models\AssetType;
use App\Models\AssetPerson;
use App\Models\Transaction\Transaction;

class SoldApplianceDataContainer
{

    public function __construct(
        private AssetType $assetType,
        private AssetPerson $assetPerson,
        private ?Transaction $transaction = null
    ) {

    }

    public function getAssetType()
    {
        return $this->assetType;
    }

    public function getAssetPerson()
    {
        return $this->assetPerson;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }
}
