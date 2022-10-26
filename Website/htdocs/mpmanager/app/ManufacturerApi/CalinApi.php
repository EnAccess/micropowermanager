<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.06.18
 * Time: 16:26
 */

namespace App\ManufacturerApi;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use SoapClient;

use function config;

class CalinApi implements IManufacturerAPI
{
    /**
     * @var Client
     */
    protected $api;
    private $transaction;
    /**
     * @var SoapClient
     */
    private $apiClient;

    public function __construct(
        Client $httpClient,
        Transaction $transaction
    ) {
        $this->api = $httpClient;
        $this->transaction = $transaction;
    }

    /**
     * @param TransactionDataContainer $transactionContainer
     * @return array (float|string)[]
     *
     * @throws Exception
     * @psalm-return array{token: string, energy: float}
     */
    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {

        $meterParameter = $transactionContainer->meterParameter;

        $transactionContainer->chargedEnergy += $transactionContainer->amount /
            ($meterParameter->tariff->total_price / 100);

        Log::critical('ENERGY TO BE CHARGED float '
            . (float)$transactionContainer->chargedEnergy . ' Manufacturer => Calin');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        }

        $meter = $transactionContainer->meter;
        $energy = (float)$transactionContainer->chargedEnergy;
        $timestamp = time();
        $cipherText = $this->generateCipherText(
            $meter->id,
            config('services.calin.user_id'),
            $meter->serial_number,
            'CreditToken',
            $energy,
            $timestamp
        );
        $tokenParams = [
            'serial_id' => $meter->id,
            'user_id' => config('services.calin.user_id'),
            'meter_id' => $meter->serial_number,
            'token_type' => 'CreditToken',
            'amount' => $energy,
            'timestamp' => $timestamp,
            'ciphertext' => $cipherText,
        ];


        $token = $this->tokenRequest($tokenParams);
        $this->associateManufacturerTransaction($transactionContainer);
        return [
            'token' => $token,
            'energy' => $energy
        ];
    }

    /**
     * Makes the external call to CALIN API and resturns the token.
     *
     *
     * @param array $tokenParams
     * @return string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function tokenRequest(array $tokenParams): string
    {
        $request = $this->api->post(
            "http://api.calinhost.com/api/token",
            [
                'body' => json_encode($tokenParams),
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                    'Content-Length:' . strlen(json_encode($tokenParams)),
                ],
            ]
        );

        $tokenResult = json_decode((string)$request->getBody(), true);
        //token generation failed, re-try to re-create the token 2 more times
        if ((int)$tokenResult['result_code'] !== 0) {
            Log::critical('Token generation failed', $tokenParams);
            throw new Exception($tokenResult['reason']);
        }
        return $tokenResult['result'];
    }


    private function initClient(): void
    {
        $this->apiClient = new SoapClient(config()->get('services.calin.api'), ['keep_alive' => false]);
    }

    private function generateCipherText(
        $serialID,
        $userID,
        $meterID,
        string $tokenType,
        float $amount,
        int $timestamp
    ): string {
        return md5(
            sprintf(
                '%s%s%s%s%s%s%s',
                $serialID,
                $userID,
                $meterID,
                $tokenType,
                $amount,
                $timestamp,
                config('services.calin.key')
            )
        );
    }


    /**
     * @param Meter $meters
     * @return void
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearMeter(Meter $meters)
    {
        throw  new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * @param TransactionDataContainer $transactionContainer
     * @return void
     */
    public function associateManufacturerTransaction(TransactionDataContainer $transactionContainer): void
    {
        $transaction = $this->transaction
            ->newQuery()
            ->with('originalAirtel', 'originalVodacom', 'orginalAgent', 'originalThirdParty')
            ->find($transactionContainer->transaction->id);
        if ($transaction->originalAirtel) {
            $transaction->originalAirtel->update([
                'manufacturer_transaction_type' => 'calin_transaction',
                'manufacturer_transaction_id' => $transaction->id
            ]);
            $transaction->originalAirtel->save();
        } elseif ($transaction->originalVodacom) {
            $transaction->originalVodacom->update([
                'manufacturer_transaction_type' => 'calin_transaction',
                'manufacturer_transaction_id' => $transaction->id
            ]);
            $transaction->originalVodacom->save();
        } elseif ($transaction->orginalAgent) {
            $transaction->orginalAgent->update([
                'manufacturer_transaction_type' => 'calin_transaction',
                'manufacturer_transaction_id' => $transaction->id
            ]);
            $transaction->orginalAgent->save();
        } elseif ($transaction->originalThirdParty) {
            $transaction->originalThirdParty->update([
                'manufacturer_transaction_type' => 'calin_transaction',
                'manufacturer_transaction_id' => $transaction->id
            ]);
            $transaction->originalThirdParty->save();
        }
    }
}
