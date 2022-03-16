<?php

namespace App\ManufacturerApi;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class CalinSmartApi implements IManufacturerAPI
{
    /**
     * @var Client
     */
    protected $api;
    private $transaction;

    public function __construct(
        Client $httpClient,
        Transaction $transaction
    ) {
        $this->api = $httpClient;
        $this->transaction = $transaction;
    }


    /**
     * @param $transactionContainer
     *
     * @return (float|mixed|string)[]
     *
     * @throws Exception
     *
     * @psalm-return array{token: mixed|string, energy: float}
     */
    public function chargeMeter($transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $transactionContainer->chargedEnergy += $transactionContainer->amount
            / ($meterParameter->tariff()->first()->total_price / 100);
        Log::critical('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => Calin Smart');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        } else {
            $meter = $transactionContainer->meter;
            $energy = (float)$transactionContainer->chargedEnergy;
            $url = config('services.calinSmart.url.purchase');
            $tokenParams = [
                'company_name' => config('services.calinSmart.company_name'),
                'user_name' => config('services.calinSmart.user_name'),
                'password' => config('services.calinSmart.password'),
                'password_vend' => config('services.calinSmart.password_vend'),
                'meter_number' => $meter->serial_number,
                'is_vend_by_unit' => true,
                'amount' => $energy

            ];
            $transactionResult = $this->tokenRequest($tokenParams, $url);

            return [
                'token' => $transactionResult['token'],
                'energy' => $energy
            ];
        }
    }

    /**
     * @param Meter $meter
     * @return array
     *
     * @throws GuzzleException
     * @psalm-return array{result_code: mixed}
     */
    public function clearMeter(Meter $meter): array
    {
        $url = config('services.calinSmart.url.clear');
        $tokenParams = [
            'company_name' => config('services.calinSmart.company_name'),
            'user_name' => config('services.calinSmart.user_name'),
            'password' => config('services.calinSmart.password'),
            'meter_number' => $meter->serial_number,
        ];
        return [
            'result_code' => $this->tokenRequest($tokenParams, $url)['result_code'],
        ];
    }

    /**
     * Makes the external call to CALIN API and resturns the token.
     *
     * @param $tokenParams
     * @param $url
     * @param (\Illuminate\Config\Repository|float|mixed|true)[] $tokenParams
     *
     * @return array
     *
     * @throws GuzzleException
     */
    private function tokenRequest(array $tokenParams, $url): array
    {
        $request = $this->api->post(
            $url,
            [
                'body' => json_encode($tokenParams),
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
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

    /**
     * @param TransactionDataContainer $transactionContainer
     * @return void
     */
    public function associateManufacturerTransaction(TransactionDataContainer $transactionContainer): void
    {
        $transaction = $this->transaction->newQuery()->with(
            'originalAirtel',
            'originalVodacom',
            'orginalAgent',
            'originalThirdParty'
        )
            ->find($transactionContainer->transaction->id);
        if ($transaction->originalAirtel) {
            $transaction->originalAirtel->update(
                [
                    'manufacturer_transaction_type' => 'calin_transaction',
                    'manufacturer_transaction_id' => $transaction->id
                ]
            );
            $transaction->originalAirtel->save();
        } elseif ($transaction->originalVodacom) {
            $transaction->originalVodacom->update(
                [
                    'manufacturer_transaction_type' => 'calin_transaction',
                    'manufacturer_transaction_id' => $transaction->id
                ]
            );
            $transaction->originalVodacom->save();
        } elseif ($transaction->orginalAgent) {
            $transaction->orginalAgent->update(
                [
                    'manufacturer_transaction_type' => 'calin_transaction',
                    'manufacturer_transaction_id' => $transaction->id
                ]
            );
            $transaction->orginalAgent->save();
        } elseif ($transaction->originalThirdParty) {
            $transaction->originalThirdParty->update(
                [
                    'manufacturer_transaction_type' => 'calin_transaction',
                    'manufacturer_transaction_id' => $transaction->id
                ]
            );
            $transaction->originalThirdParty->save();
        }
    }
}
