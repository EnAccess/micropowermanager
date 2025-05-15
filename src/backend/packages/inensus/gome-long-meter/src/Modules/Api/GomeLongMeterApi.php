<?php

namespace Inensus\GomeLongMeter\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use Illuminate\Support\Facades\Log;
use Inensus\GomeLongMeter\Models\GomeLongTransaction;
use Inensus\GomeLongMeter\Services\GomeLongCredentialService;

class GomeLongMeterApi implements IManufacturerAPI {
    public const API_CALL_TOKEN_GENERATION = '/EKPower';

    public function __construct(
        private GomeLongCredentialService $credentialService,
        private GomeLongTransaction $gomeLongTransaction,
        private ApiRequests $apiRequests,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $tariff->total_price;

        Log::debug('ENERGY TO BE CHARGED float '.(float) $transactionContainer->chargedEnergy.
            ' Manufacturer => GomeLongMeterApi');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float) $transactionContainer->chargedEnergy,
            ];
        } else {
            $energy = (float) $transactionContainer->chargedEnergy;
            $credentials = $this->credentialService->getCredentials();
            $params = [
                'U' => $credentials->getUserId(),
                'K' => $credentials->getUserPassword(),
                'meter' => $meter->serial_number,
                'amt' => (float) $transactionContainer->chargedEnergy,
            ];

            $response = $this->apiRequests->post($credentials, $params, self::API_CALL_TOKEN_GENERATION);

            $manufacturerTransaction = $this->gomeLongTransaction->newQuery()->create([]);
            $transactionContainer->transaction->originalTransaction()->first()->update([
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'gome_long_transaction',
            ]);

            return [
                'token' => $response['Token'],
                'token_type' => Token::TYPE_ENERGY,
                'token_unit' => Token::UNIT_KWH,
                'token_amount' => $energy,
            ];
        }
    }

    public function clearDevice(Device $device) {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
