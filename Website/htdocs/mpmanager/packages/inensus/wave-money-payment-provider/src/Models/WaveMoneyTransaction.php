<?php

namespace Inensus\WaveMoneyPaymentProvider\Models;

use App\Models\BaseModel;

/**
 * @property int id
 * @property int amount
 * @property string currency
 * @property string order_id
 * @property string reference_id
 * @property string status
 * @property string external_transaction_id
 * @property int customer_id
 * @property null|string meter_serial
 */
class WaveMoneyTransaction extends BaseModel
{

    public const STATUS_REQUESTED = 2;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 0;

    protected $table = 'wave_money_transactions';

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getOrderId(): string
    {
        return $this->order_id;
    }

    public function getReferenceId(): string
    {
        return $this->reference_id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setStatus(int $status)
    {
        $this->status= $status;
    }

    public function setExternalTransactionId(string $transactionId)
    {
        $this->external_transaction_id = $transactionId;
    }

    public function findByOrderId(string $orderId): ?self
    {
        /** @var null|WaveMoneyTransaction $result */
        $result =  $this->newQuery()->where('order_id', '=', $orderId)
            ->first();

        return $result;
    }

    public function setOrderId(string $orderId)
    {
        $this->order_id = $orderId;
    }

    public function setReferenceId(string $referenceId)
    {
        $this->reference_id = $referenceId;
    }

    public function setCustomerId(int $customerId)
    {
        $this->customer_id  =$customerId;
    }

    public function setMeterSerial(string $meterSerialNumber)
    {
        $this->meter_serial = $meterSerialNumber;
    }

    public function setAmount(float $amount)
    {
        $this->amount = $amount;
    }
}
