<?php

namespace App\Plugins\SparkMeter;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\SparkMeter\Exceptions\SparkAPIResponseException;
use App\Plugins\SparkMeter\Http\Requests\SparkMeterApiRequests;
use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Plugins\SparkMeter\Models\SmTariff;
use App\Plugins\SparkMeter\Models\SmTransaction;
use App\Plugins\SparkMeter\Services\TariffService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class SparkMeterApi implements IManufacturerAPI {
    private string $rootUrl = '/transaction/';

    public function __construct(
        private readonly SparkMeterApiRequests $sparkMeterApiRequests,
        private readonly TariffService $tariffService,
        private readonly SmCustomer $smCustomer,
        private readonly SmTransaction $smTransaction,
        private readonly SmTariff $smTariff,
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
        $tariff = $transactionContainer->tariff;
        $owner = $transactionContainer->device->person;

        $smTariff = $this->smTariff->newQuery()->where(
            'mpm_tariff_id',
            $tariff->id
        )->first();
        $tariff = $this->tariffService->singleSync($smTariff);
        $transactionContainer->chargeAmount += $transactionContainer->amount / $tariff->total_price;
        $transactionContainer->chargeUnit = Token::UNIT_KWH;
        $transactionContainer->chargeType = Token::TYPE_ENERGY;
        Log::critical('ENERGY TO BE CHARGED float '.
            $transactionContainer->chargeAmount.
            ' Manufacturer => Spark');

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => $transactionContainer->chargeAmount,
            ];
        }
        $amount = $transactionContainer->transaction->amount;
        $externalId = $transactionContainer->transaction->id;

        try {
            $smCustomer = $this->smCustomer->newQuery()->with('site')->where(
                'mpm_customer_id',
                $owner->id
            )->firstOrFail();
        } catch (ModelNotFoundException $e) {
            Log::critical('No Customer found for transaction data.', ['message' => $e->getMessage()]);
            throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
        }

        $postParams = [
            'customer_id' => $smCustomer->customer_id,
            'amount' => strval($amount),
            'source' => 'cash',
            'external_id' => strval($externalId),
            'memo' => 'MPM transaction #'.$externalId,
        ];

        try {
            // Routed through SparkMeterApiRequests rather than the raw Guzzle
            // client: it translates GuzzleException into SparkAPIResponseException
            // and runs the response through ResultStatusChecker, which is the
            // error-shape check this method used to (incorrectly) duplicate below.
            $result = $this->sparkMeterApiRequests->post(
                $this->rootUrl,
                $postParams,
                $smCustomer->site->site_id
            );
        } catch (SparkAPIResponseException $e) {
            Log::critical(
                'Spark API Transaction Failed',
                ['Body :' => json_encode($postParams), 'message :' => $e->getMessage()]
            );
            throw $e;
        }
        $transactionInformation = $this->sparkMeterApiRequests->getInfo(
            $this->rootUrl,
            $result['transaction_id'] ?? null,
            $smCustomer->site->site_id
        );

        $transactionResult = [
            'transaction_id' => $result['transaction_id'] ?? null,
            'site_id' => $smCustomer->site->site_id,
            'customer_id' => $smCustomer->customer_id,
            'status' => $transactionInformation['transaction']['status'],
            'external_id' => intval($transactionInformation['transaction']['external_id']),
            'timestamp' => $transactionInformation['transaction']['created'],
        ];

        $manufacturerTransaction = $this->smTransaction->newQuery()->create([
            'transaction_id' => $transactionResult['transaction_id'],
            'site_id' => $transactionResult['site_id'],
            'customer_id' => $transactionResult['customer_id'],
            'status' => $transactionResult['status'],
            'external_id' => $transactionResult['external_id'],
            'timestamp' => $transactionResult['timestamp'],
        ]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'sm_transaction',
        ]);

        $token = $smCustomer->site->site_id.'-'.
            $transactionInformation['transaction']['source'].'-'.
            $smCustomer->customer_id.'-'.
            $transactionResult['transaction_id'];

        return [
            'token' => $token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $transactionContainer->chargeAmount,
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
        // TODO: Implement clearDevice() method.
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
