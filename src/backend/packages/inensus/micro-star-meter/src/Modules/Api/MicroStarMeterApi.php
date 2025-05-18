<?php

namespace Inensus\MicroStarMeter\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use Illuminate\Support\Facades\Log;
use Inensus\MicroStarMeter\Models\MicroStarTransaction;
use Inensus\MicroStarMeter\Services\MicroStarCredentialService;

class MicroStarMeterApi implements IManufacturerAPI {
    public const API_CALL_CHARGE_METER = '/getStsVendingToken?';

    public function __construct(
        private MicroStarCredentialService $credentialService,
        private MicroStarTransaction $microStarTransaction,
        private ApiRequests $apiRequests,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        Log::debug('ENERGY TO BE CHARGED float '.(float) $transactionContainer->chargedEnergy.
            ' Manufacturer => MicroStarMeterApi');

        $energy = (float) $transactionContainer->chargedEnergy;
        $params = ['deviceNo' => $meter->serial_number, 'rechargeAmount' => $energy]; // if they accepts
        // rechargeAmount as money, then we have to convert it to money
        $credentials = $this->credentialService->getCredentials();
        if (config('app.env') === 'local' || config('app.env') === 'development') {
            // debug token for development
            $response['token'] = '48725997619297311927';
        } else {
            $response = $this->apiRequests->get($credentials, $params, self::API_CALL_CHARGE_METER);
        }

        $manufacturerTransaction = $this->microStarTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'micro_star_transaction',
        ]);

        return [
            'token' => $response['token'],
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    public function clearDevice(Device $device) {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
