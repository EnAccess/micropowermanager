<?php

namespace App\Misc;

use App\Models\AssetType;
use App\Models\AssetPerson;
use App\Models\Transaction\Transaction;

class SoldApplianceDataContainer
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var AssetPerson
     */
    private $assetPerson;

    /**
     * @var AssetType
     */
    private $assetType;

    public function __construct(AssetType $assetType, AssetPerson $assetPerson, Transaction $transaction = null)
    {
        $this->assetPerson = $assetPerson;
        $this->assetType = $assetType;
        $this->transaction = $transaction;
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
