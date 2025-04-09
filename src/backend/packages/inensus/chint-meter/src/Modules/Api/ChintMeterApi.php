<?php

namespace Inensus\ChintMeter\Modules\Api;

use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use Illuminate\Support\Facades\Log;
use Inensus\ChintMeter\Exceptions\TariffPriceDoesNotMatchException;
use Inensus\ChintMeter\Models\ChintTransaction;
use Inensus\ChintMeter\Services\ChintCredentialService;

class ChintMeterApi implements IManufacturerAPI {
    public function __construct(
        private ChintCredentialService $credentialService,
        private ChintTransaction $chintTransaction,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;

        Log::debug('ENERGY TO BE CHARGED float '.(float) $transactionContainer->chargedEnergy.
            ' Manufacturer => ChintMeterApi');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float) $transactionContainer->chargedEnergy,
            ];
        }

        $credentials = $this->credentialService->getCredentials();
        $amount = $transactionContainer->amount;
        $customerId = $meter->serial_number;
        $chintSoap = new ChintSoap($credentials, $customerId, $amount);
        $rechargeToken = null;
        $chargedEnergy = 0;

        try {
            $validationResponse = $chintSoap->validation();
            $tariffPrice = $validationResponse['tariffPrice'];

            if ($tariffPrice !== $tariff->total_price) {
                Log::critical("Tariff price {$tariffPrice} does not match with tariff name {$tariff->name}. Contact with Chint meter support.");
                throw new TariffPriceDoesNotMatchException('Tariff price does not match');
            }

            $transactionResponse = $chintSoap->transaction();
            $rechargeToken = $transactionResponse['rechargeToken'];
            $chargedEnergy = $transactionResponse['chargedEnergy'];
        } catch (\Exception $e) {
            Log::error('ChintMeterApi error: '.$e->getMessage());
            throw $e;
        }

        $manufacturerTransaction = $this->chintTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'chint_transaction',
        ]);

        return [
            'token' => $rechargeToken,
            'load' => $chargedEnergy,
        ];
    }

    public function clearDevice(Device $device) {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
