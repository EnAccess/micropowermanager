<?php

namespace App\Plugins\CalinMeter;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\CalinMeter\Exceptions\CalinApiResponseException;
use App\Plugins\CalinMeter\Http\Clients\CalinMeterApiClient;
use App\Plugins\CalinMeter\Models\CalinCredential;
use App\Plugins\CalinMeter\Models\CalinTransaction;
use App\Plugins\CalinMeter\Services\CalinCredentialService;
use Illuminate\Support\Facades\Log;

class CalinMeterApi implements IManufacturerAPI {
    public function __construct(
        private CalinTransaction $calinTransaction,
        private CalinCredentialService $credentialService,
        private CalinMeterApiClient $calinMeterApiClient,
    ) {}

    /**
     * @return list<ManufacturerCapability>
     */
    public function capabilities(): array {
        return [
            ManufacturerCapability::CreditToken,
        ];
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        // we round the energy to be charged to 1 decimal place because the api only accepts 1 decimal place.
        $transactionContainer->chargeAmount += round($transactionContainer->amount / $tariff->total_price, 1);
        $transactionContainer->chargeUnit = Token::UNIT_KWH;
        $transactionContainer->chargeType = Token::TYPE_ENERGY;
        Log::debug('ENERGY TO BE CHARGED float '.$transactionContainer->chargeAmount.
            ' Manufacturer => CalinMeterApi');
        $credentials = $this->credentialService->getCredentials();
        if (!$credentials instanceof CalinCredential || $credentials->user_id === null || $credentials->api_key === null) {
            throw new CalinApiResponseException('Calin credentials are not configured.');
        }
        $energy = $transactionContainer->chargeAmount;

        $token = $this->calinMeterApiClient->vendCreditToken(
            $credentials->api_url,
            $credentials->user_id,
            $credentials->api_key,
            $meter->serial_number,
            $energy,
        );

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

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function unlockDevice(TransactionDataContainer $transactionContainer): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
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
