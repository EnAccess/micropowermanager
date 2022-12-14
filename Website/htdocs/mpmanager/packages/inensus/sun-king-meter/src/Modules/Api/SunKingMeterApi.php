<?php

namespace Inensus\SunKingMeter\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use Illuminate\Support\Facades\Log;
use Inensus\SunKingMeter\Models\SunKingTransaction;
use Inensus\SunKingMeter\Services\SunKingCredentialService;


class SunKingMeterApi implements IManufacturerAPI
{
    const API_CALL_TOKEN_GENERATION = '/token';
    const COMMAND_ADD_CREDIT = 'add_credit';

    public function __construct(
        private SunKingCdentialService $credentialService,
        private SunKingTransaction $sunKingTransaction,
        private ApiRequests $apiRequests
    ) {

    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $meterParameter = $transactionContainer->meterParameter;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / ($meterParameter->tariff->total_price);

        Log::debug('ENERGY TO BE CHARGED float ' . (float)$transactionContainer->chargedEnergy .
            ' Manufacturer => SunKingMeterApi');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        } else {

            $meter = $transactionContainer->meter;
            $energy = (float)$transactionContainer->chargedEnergy;

            $params = [
                "device" => $meter->serial_number,
                "command" => self::COMMAND_ADD_CREDIT,
                "payload" => $energy
            ];

            $credentials = $this->credentialService->getCredentials();

            if (!$this->credentialService->isAccessTokenValid($credentials)) {
                $authResponse = $this->apiRequests->authentication($credentials);
                $credentials->update([$authResponse]);
            }

            $response = $this->apiRequests->post($credentials, $params, self::API_CALL_TOKEN_GENERATION);

            $manufacturerTransaction = $this->sunKingTransaction->newQuery()->create([]);
            $transactionContainer->transaction->originalTransaction()->first()->update([
                'manufacturer_transaction_id' => $manufacturerTransaction->id,
                'manufacturer_transaction_type' => 'micro_star_transaction',
            ]);

            return [
                'token' => $response['token'],
                'energy' => $energy
            ];
        }
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


}
