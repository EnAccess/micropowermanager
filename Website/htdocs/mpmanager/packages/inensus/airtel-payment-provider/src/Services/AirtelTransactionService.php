<?php
namespace Inensus\AirtelPaymentProvider\Services;

use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AirtelTransactionService
{
    const MINIMUM_TRANSACTION_AMOUNT = 900;
    const MAXIMUM_TRANSACTION_DAILY_TRANSACTION_COUNT = 2;

    protected string $payerPhoneNumber;
    protected string $meterSerialNumber;
    protected int $customerId;
    protected float $amount;

    private Meter $meter;
    private Address $address;
    private Transaction $transaction;
    private MeterParameter $meterParameter;
    private AirtelTransaction $airtelTransaction;

    public function __construct(
        Meter $meter,
        Address $address,
        Transaction $transaction,
        MeterParameter $meterParameter,
        AirtelTransaction $airtelTransaction
    ) {

        $this->meter = $meter;
        $this->address = $address;
        $this->transaction = $transaction;
        $this->meterParameter = $meterParameter;
        $this->airtelTransaction = $airtelTransaction;
    }

    public function validatePaymentOwner(string $meterSerialNumber, float $amount): void
    {
        $meter = $this->meter->newQuery()->where('serial_number', '=', $meterSerialNumber)
            ->first();

        if (!$meter) {
            throw new ModelNotFoundException('Meter not found with serial number you entered');
        }

        if (!$meterTariff = $meter->meterParameter->tariff) {
            throw new ModelNotFoundException('Tariff not found with meter serial number you entered');
        }

        $customerId = $meter->MeterParameter->owner_id;

        if (!$customerId) {
            throw new ModelNotFoundException('Customer not found with meter serial number you entered');
        }

        $this->meterSerialNumber = $meterSerialNumber;
        $this->customerId = $customerId;
        $this->amount = $amount;

        try {
            $this->payerPhoneNumber = $this->getTransactionSender($meterSerialNumber);
        } catch (\Exception $exception) {
            throw  new \Exception($exception->getMessage());
        }
    }

    private function getTransactionSender($meterSerialNumber)
    {
        $meterParameter = $this->meterParameter->newQuery()
            ->whereHas('meter',
                function ($q) use ($meterSerialNumber) {
                    $q->where('serial_number', $meterSerialNumber);
                })->first();

        $personId = $meterParameter->owner_id;
        try {
            $address = $this->address->newQuery()
                ->whereHasMorph('owner', [Person::class],
                    function ($q) use ($personId) {
                        $q->where('owner_id', $personId);
                    })->where('is_primary', 1)->firstOrFail();
            return $address->phone;
        } catch (ModelNotFoundException $exception) {
            throw new \Exception('No phone number record found by customer.');
        }
    }

    public function initializeTransactionData($transactionData): array
    {
        return [
            'interface_id' => $transactionData['MERCHANTMSISDN'],
            'business_number' => $transactionData['MERCHANTMSISDN'],
            'status' => 0, // 0 for pending
            'trans_id' => "",
            'tr_id' => $transactionData['REFERENCE1'],
        ];

    }


    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getPayerPhoneNumber()
    {
        return $this->payerPhoneNumber;
    }

    public function getMeterSerialNumber()
    {
        return $this->meterSerialNumber;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function imitateTransactionForValidation(array $transactionData, $amount)
    {
        $this->airtelTransaction = $this->airtelTransaction->newQuery()->make($transactionData);

        $this->transaction = $this->transaction->newQuery()->make([
            'amount' => $amount,
            'sender' => $this->payerPhoneNumber,
            'message' => $this->meterSerialNumber,
            'type' => 'energy',
            'original_transaction_type' => 'airtel_transaction',
        ]);

        $this->isImitationTransactionValid($this->transaction);
    }

    private function isImitationTransactionValid($transaction)
    {
        try {
            TransactionDataContainer::initialize($transaction);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $toDaysTransactionsCount = $this->getApprovedTransactionCountForToDay($this->transaction->message);


        if ($toDaysTransactionsCount >= self::MAXIMUM_TRANSACTION_DAILY_TRANSACTION_COUNT) {

            throw new \Exception("{$this->transaction->message} tries for more then 2 approved transaction with amount {$this->transaction->amount}");
        }

        if ($this->transaction->amount < self::MINIMUM_TRANSACTION_AMOUNT) {
            throw new \Exception("{$this->transaction->message} tries for transaction with amount {$this->transaction->amount} which is less than minimum amount");
        }

    }

    private function getApprovedTransactionCountForToDay($meterSerialNumber)
    {
        return Transaction::with('originalAirtel', 'originalVodacom')
            ->where('message', $meterSerialNumber)
            ->where('created_at', '>=', now()->startOfDay())
            ->whereHasMorph('originalTransaction', [VodacomTransaction::class, AirtelTransaction::class],
                static function ($q) {
                    $q->where('status', 1);
                })->count();
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getAirtelTransaction(): AirtelTransaction
    {
        return $this->airtelTransaction;
    }

    public function saveTransaction(): Transaction
    {
        $this->airtelTransaction->save();
        $this->transaction->originalTransaction()->associate($this->airtelTransaction)->save();
        return $this->transaction;
    }

    public function getByTrId($trId)
    {
        return $this->airtelTransaction->newQuery()->where('tr_id', $trId)->firstOrFail();
    }
}