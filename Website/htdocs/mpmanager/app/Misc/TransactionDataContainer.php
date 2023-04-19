<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 03.07.18
 * Time: 10:42
 */

namespace App\Misc;

use App\Exceptions\Meters\MeterIsNotAssignedToCustomer;
use App\Exceptions\Meters\MeterIsNotInUse;
use App\Exceptions\Tariffs\TariffNotFound;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterToken;
use App\Models\Transaction\Transaction;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TransactionDataContainer
{
    /**
     * @var int
     */
    public $accessRateDebt;
    /**
     * @var Transaction
     */
    public $transaction;

    /**
     * @var Meter
     */
    public $meter;

    /**
     * @var MeterParameter
     */
    public $meterParameter;

    /**
     * @var MeterTariff
     */
    public $tariff;

    /**
     * @var Manufacturer
     */
    public $manufacturer;

    /**
     * @var MeterToken
     */
    public $token;

    /**
     * @var array
     */
    public $paid_rates;

    /**
     * @var float
     */
    public $chargedEnergy;

    public $amount;

    public $totalAmount;

    /**
     * @param Transaction $transaction
     *
     * @param bool $withToken
     *
     * @return TransactionDataContainer
     * @throws Exception
     */
    public static function initialize(Transaction $transaction, bool $withToken = false): TransactionDataContainer
    {
        $container = new self();
        $container->chargedEnergy = 0;

        $container->transaction = $transaction;
        $container->totalAmount = $transaction->amount;
        $container->amount = $transaction->amount;
        //get meter
        try {
            $container->meter = $container->getMeter($transaction->message);
            $container->meterParameter = $container->getMeterParameter($container->meter);
            $container->tariff = $container->getTariff($container->meterParameter);
            $container->manufacturer = $container->getManufacturer($container->meter);
        } catch (ModelNotFoundException $e) {
            throw new Exception('Meter Serial number ' . $transaction->message . 'not found in the database');
        } catch (MeterIsNotInUse $e) {
            throw new Exception($e->getMessage());
        } catch (TariffNotFound $e) {
            throw new Exception($e->getMessage(). 'Meter : '. $transaction->message);
        } catch (MeterIsNotAssignedToCustomer $e) {
            throw new Exception($e->getMessage());
        }
        if ($withToken) {
            try {
                $container->token = $transaction->token()->firstOrFail();
            } catch (ModelNotFoundException $exception) {
                Log::critical(
                    'The token for' . $transaction->message . ' not found',
                    ['id' => 3424342376236]
                );
                throw new RuntimeException($exception->getMessage());
            }
        }
        return $container;
    }


    /**
     * @param String $serialNumber
     *
     * @return mixed
     * @throws MeterIsNotInUse
     */
    private function getMeter(string $serialNumber)
    {
        $meter = Meter::where('serial_number', $serialNumber)->firstOrFail();
        //meter is not been used by anyone
        if (!$meter->in_use) {
            throw new MeterIsNotInUse($serialNumber . ' meter is not in use');
        }
        return $meter;
    }

    /**
     * @param Meter $meter
     *
     * @return MeterParameter
     * @throws MeterIsNotAssignedToCustomer
     */
    private function getMeterParameter(Meter $meter): MeterParameter
    {
        $meterParameter = $meter->meterParameter()->firstOrFail();
        if ($meterParameter === null) {
            throw new MeterIsNotAssignedToCustomer($meter->serial_number . 'is not assigned to any customer');
        }
        return $meterParameter;
    }

    /**
     * @param MeterParameter $meterParameter
     *
     * @return MeterTariff|Model|BelongsTo|object
     * @throws TariffNotFound
     *
     */
    private function getTariff(MeterParameter $meterParameter)
    {
        $tariff = $meterParameter->tariff()->first();
        if ($tariff === null) {
            throw new TariffNotFound($meterParameter->tariff_id .
                ' is assigned to the meter, but the tariff does not exit');
        }
        return $tariff;
    }

    /**
     * @param Meter $meter
     *
     * @return Model|BelongsTo|null|object
     */
    private function getManufacturer(Meter $meter)
    {
        return $meter->manufacturer()->first();
    }
}
