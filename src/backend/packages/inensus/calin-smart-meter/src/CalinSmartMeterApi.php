<?php

namespace Inensus\CalinSmartMeter;

use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\CalinSmartMeter\Exceptions\CalinSmartCreadentialsNotFoundException;
use Inensus\CalinSmartMeter\Http\Requests\CalinSmartMeterApiRequests;
use Inensus\CalinSmartMeter\Models\CalinSmartCredential;
use Inensus\CalinSmartMeter\Models\CalinSmartTransaction;

class CalinSmartMeterApi implements IManufacturerAPI {
    protected $api;
    private $rootUrl = '/POS_Purchase/';

    public function __construct(
        Client $httpClient,
        private CalinSmartTransaction $calinSmartTransaction,
        private CalinSmartCredential $credentials,
        private CalinSmartMeterApiRequests $calinSmartMeterApiRequests,
    ) {
        $this->api = $httpClient;
    }

    public function chargeDevice($transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        Log::critical('ENERGY TO BE CHARGED float '.(float) $transactionContainer->chargedEnergy.
            ' Manufacturer => Calin Smart');

        $energy = (float) $transactionContainer->chargedEnergy;
        try {
            $credentials = $this->credentials->newQuery()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new CalinSmartCreadentialsNotFoundException($e->getMessage());
        }
        $url = $credentials->api_url.$this->rootUrl;
        $tokenParams = [
            'company_name' => $credentials->company_name,
            'user_name' => $credentials->user_name,
            'password' => $credentials->password,
            'password_vend' => $credentials->password_vend,
            'meter_number' => $meter->serial_number,
            'is_vend_by_unit' => true,
            'amount' => $energy,
        ];
        if (config('app.env') === 'demo' || config('app.env') === 'development') {
            // debug token for development
            $token = '48725997619297311927';
        } else {
            $token = $this->calinSmartMeterApiRequests->post($url, $tokenParams);
        }
        $manufacturerTransaction = $this->calinSmartTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'calin_smart_transaction',
        ]);

        return [
            'token' => $token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    /**
     * @param Meter $meter
     *
     * @throws GuzzleException
     *
     * @psalm-return array{result_code: mixed}
     */
    public function clearDevice(Device $device) {
        $meter = $device->device;
        $root = '/Maintenance_ClearCredit/';
        try {
            $credentials = $this->credentials->newQuery()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new CalinSmartCreadentialsNotFoundException($e->getMessage());
        }
        $url = $credentials->api_url.$root;
        $tokenParams = [
            'company_name' => $credentials->company_name,
            'user_name' => $credentials->password,
            'password' => $credentials->password_vend,
            'meter_number' => $meter->serial_number,
        ];

        return [
            'result_code' => $this->calinSmartMeterApiRequests->post($url, $tokenParams),
        ];
    }
}
