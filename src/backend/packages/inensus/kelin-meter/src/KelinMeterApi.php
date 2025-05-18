<?php

namespace Inensus\KelinMeter;

use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Token;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinMeter;
use Inensus\KelinMeter\Models\KelinTransaction;

class KelinMeterApi implements IManufacturerAPI {
    private $rootUrl = '/recharge';

    public function __construct(
        private KelinMeter $kelinMeter,
        private KelinTransaction $kelinTransaction,
        private KelinMeterApiClient $kelinApi,
    ) {}

    public function chargeDevice($transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;
        Log::critical('ENERGY TO BE CHARGED float '.
            (float) $transactionContainer->chargedEnergy.
            ' Manufacturer => Kelin');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float) $transactionContainer->chargedEnergy,
            ];
        } else {
            $amount = $transactionContainer->totalAmount;
            try {
                $kelinMeter = $this->kelinMeter->newQuery()->where(
                    'mpm_meter_id',
                    $meter->id
                )->firstOrFail();
            } catch (ModelNotFoundException $e) {
                Log::critical('No Meter found for transaction data.', ['message' => $e->getMessage()]);
                throw new ModelNotFoundException($e->getMessage());
            }
            $queryParams = [
                'meterNo' => $kelinMeter->meter_address,
                'tariff' => $tariff->total_price,
                'recharge' => $amount,
                'energy' => $transactionContainer->chargedEnergy,
                'rechargeTime' => Carbon::now()->format('Y-m-d'),
            ];

            try {
                $result = $this->kelinApi->get($this->rootUrl, $queryParams);
            } catch (KelinApiResponseException $exception) {
                Log::critical(
                    'Kelin API Transaction Failed',
                    ['Body :' => json_encode($queryParams), 'message :' => $exception->getMessage()]
                );
                throw new KelinApiResponseException($exception->getMessage());
            } catch (GuzzleException $exception) {
                Log::critical(
                    'Unknown exception while authenticating KelinMeter',
                    ['reason' => $exception->getMessage()]
                );
                throw new KelinApiResponseException($exception->getMessage());
            }
            $transactionResult = [
                'opType' => $result['data']['opType'],
                'payKWH' => $result['data']['payKWH'],
                'openToken1' => $result['data']['openToken1'],
                'openToken2' => $result['data']['openToken2'],
                'payToken' => $result['data']['payToken'],
                'meterSerial' => $meter->serial_number,
                'amount' => $amount,
            ];

            $manufacturerTransaction = $this->kelinTransaction->newQuery()->create([
                'meter_serial' => $transactionResult['meterSerial'],
                'amount' => $transactionResult['amount'],
                'op_type' => $transactionResult['opType'],
                'pay_kwh' => $transactionResult['payKWH'],
                'open_token_1' => $transactionResult['openToken1'],
                'open_token_2' => $transactionResult['openToken2'],
                'pay_token' => $transactionResult['payToken'],
            ]);

            $transactionContainer->transaction->originalTransaction()->first()->update([
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'kelin_transaction',
            ]);

            $token = $transactionResult['opType'] === 2 ? sprintf(
                'EnergyToken : %s',
                $transactionResult['payToken']
            ) :
                sprintf(
                    'OpenToken1 : %s OpenToken2 : %s',
                    $transactionResult['openToken1'],
                    $transactionResult['openToken2']
                );

            return [
                'token' => $token,
                'token_type' => Token::TYPE_ENERGY,
                'token_unit' => Token::UNIT_KWH,
                'token_amount' => $transactionContainer->chargedEnergy,
            ];
        }
    }

    public function clearDevice(Device $device) {}
}
