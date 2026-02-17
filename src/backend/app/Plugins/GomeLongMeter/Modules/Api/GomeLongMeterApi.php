<?php

namespace App\Plugins\GomeLongMeter\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\GomeLongMeter\Models\GomeLongTransaction;
use App\Plugins\GomeLongMeter\Services\GomeLongCredentialService;
use Illuminate\Support\Facades\Log;

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

        Log::debug('ENERGY TO BE CHARGED float '.$transactionContainer->chargedEnergy.
            ' Manufacturer => GomeLongMeterApi');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => $transactionContainer->chargedEnergy,
            ];
        } else {
            $energy = $transactionContainer->chargedEnergy;
            $credentials = $this->credentialService->getCredentials();
            $params = [
                'U' => $credentials->getUserId(),
                'K' => $credentials->getUserPassword(),
                'meter' => $meter->serial_number,
                'amt' => $transactionContainer->chargedEnergy,
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

    /**
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
