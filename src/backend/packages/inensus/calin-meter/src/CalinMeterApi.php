<?php

namespace Inensus\CalinMeter;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inensus\CalinMeter\Http\Requests\CalinMeterApiRequests;
use Inensus\CalinMeter\Models\CalinCredential;
use Inensus\CalinMeter\Models\CalinTransaction;

class CalinMeterApi implements IManufacturerAPI {
    public const CREDIT_TOKEN = 'CreditToken';
    protected $api;
    private $rootUrl = '/tokennew';

    public function __construct(
        Client $httpClient,
        private CalinTransaction $calinTransaction,
        private CalinCredential $credentials,
        private CalinMeterApiRequests $calinMeterApiRequests,
    ) {
        $this->api = $httpClient;
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        // we round the energy to be charged to 1 decimal place because the api only accepts 1 decimal place.
        $transactionContainer->chargedEnergy += round($transactionContainer->amount / $tariff->total_price, 1);
        Log::debug('ENERGY TO BE CHARGED float '.(float) $transactionContainer->chargedEnergy.
            ' Manufacturer => CalinMeterApi');
        $credentials = $this->credentials->newQuery()->firstOrFail();
        $energy = (float) $transactionContainer->chargedEnergy;

        $tokenParams = [
            'user_id' => $credentials->user_id,
            'password' => $credentials->api_key,
            'meter_id' => $meter->serial_number,
            'token_type' => self::CREDIT_TOKEN,
            'amount' => $energy,
        ];

        $url = $credentials->api_url.$this->rootUrl;
        if (config('app.env') === 'demo' || config('app.env') === 'development') {
            // debug token for development
            $token = '48725997619297311927';
        } else {
            $token = $this->calinMeterApiRequests->post($url, $tokenParams);
        }

        $manufacturerTransaction = $this->calinTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'calin_transaction',
        ]);

        return [
            'token' => $token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    public function clearDevice(Device $device) {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
