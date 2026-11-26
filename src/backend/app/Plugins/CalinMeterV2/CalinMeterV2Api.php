<?php

namespace App\Plugins\CalinMeterV2;

use App\DTO\TransactionDataContainer;
use App\Enums\VendingCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerDeviceControl;
use App\Lib\ITokenIssuer;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiResponseException;
use App\Plugins\CalinMeterV2\Http\Clients\CalinMeterV2ApiClient;
use App\Plugins\CalinMeterV2\Models\CalinV2Transaction;
use App\Plugins\CalinMeterV2\Services\CalinV2CredentialService;
use Illuminate\Support\Arr;

class CalinMeterV2Api implements ITokenIssuer, IManufacturerDeviceControl {
    private const string CREDIT_TOKEN_PATH = '/api/token/creditToken/generate';
    private const string CLEAR_CREDIT_TOKEN_PATH = '/api/token/clearCreditToken/generate';
    private const string METER_READ_PATH = '/api/meter/read';

    public function __construct(
        private CalinV2CredentialService $credentialService,
        private CalinMeterV2ApiClient $apiClient,
        private CalinV2Transaction $calinV2Transaction,
    ) {}

    /**
     * @return list<VendingCapability>
     */
    public function capabilities(): array {
        return [
            VendingCapability::AddCredit,
            VendingCapability::ClearCredit,
        ];
    }

    /**
     * The meter decides how much energy the token finally carries, so the `totalUnit`
     * Calin reports is recorded in preference to what the tariff implied.
     */
    public function addCredit(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $credentials = $this->credentialService->authenticatedCredentials();
        $energyFromTariff = $transactionContainer->amount / $transactionContainer->tariff->total_price;

        $result = $this->apiClient->post($credentials, self::CREDIT_TOKEN_PATH, [
            'meterId' => $meter->serial_number,
            'isPreview' => false,
            // Vend by unit: the amount below is energy, not money.
            'isVendByTotalPaid' => false,
            'amount' => $energyFromTariff,
        ]);

        $token = $result['token'] ?? null;
        if (!is_scalar($token) || (string) $token === '') {
            throw new CalinV2ApiResponseException("Calin accepted the vend for meter {$meter->serial_number} but returned no token.");
        }

        $energy = is_numeric($result['totalUnit'] ?? null) ? (float) $result['totalUnit'] : $energyFromTariff;
        $transactionContainer->chargeAmount = $energy;
        $transactionContainer->chargeUnit = Token::UNIT_KWH;
        $transactionContainer->chargeType = Token::TYPE_ENERGY;

        $manufacturerTransaction = $this->calinV2Transaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'calin_v2_transaction',
        ]);

        return [
            'token' => (string) $token,
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
    public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function resetCredit(Device $device): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    public function clearCredit(Device $device): array {
        $result = $this->apiClient->post($this->credentialService->authenticatedCredentials(), self::CLEAR_CREDIT_TOKEN_PATH, [
            'meterId' => $device->device_serial,
        ]);

        $token = $result['token'] ?? null;
        if (!is_scalar($token) || (string) $token === '') {
            throw new CalinV2ApiResponseException("Calin accepted the clear credit request for meter {$device->device_serial} but returned no token.");
        }

        return [
            'token' => (string) $token,
            'token_type' => Token::TYPE_CLEAR_CREDIT,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    /**
     * An empty result means Calin does not know the serial, which is reported as not
     * mapped rather than as a failure.
     *
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array {
        $result = $this->apiClient->post($this->credentialService->authenticatedCredentials(), self::METER_READ_PATH, [
            'meterId' => $device->device_serial,
            'pageNumber' => 1,
            'pageSize' => 1,
        ]);
        $meterInfo = $result['data'][0] ?? null;

        if (!is_array($meterInfo)) {
            return ['mapped' => false, 'device' => null];
        }

        return [
            'mapped' => true,
            'device' => Arr::only($meterInfo, ['meterId', 'type', 'protocolVersion', 'sgc', 'status']),
        ];
    }
}
