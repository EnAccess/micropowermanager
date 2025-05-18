<?php

namespace Inensus\SteamaMeter;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaTransaction;
use Inensus\SteamaMeter\Services\SteamaCredentialService;
use Inensus\SteamaMeter\Services\SteamaCustomerService;

class SteamaMeterApi implements IManufacturerAPI {
    protected $api;

    public function __construct(
        Client $httpClient,
        private SteamaCustomer $steamaCustomer,
        private SteamaCredentialService $credentialService,
        private SteamaCustomerService $customerService,
        private SteamaTransaction $steamaTransaction,
    ) {
        $this->api = $httpClient;
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $owner = $transactionContainer->device->person;

        $stmCustomer = $this->steamaCustomer->newQuery()->with('paymentPlans')->where(
            'mpm_customer_id',
            $owner->id
        )->first();

        $customerId = $stmCustomer->customer_id;
        $stmCustomer = $this->customerService->syncTransactionCustomer($stmCustomer->id);
        $customerEnergyPrice = $stmCustomer->energy_price;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / $customerEnergyPrice;

        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'load' => (float) $transactionContainer->chargedEnergy,
            ];
        } else {
            $amount = $transactionContainer->totalAmount;
            $postParams = [
                'amount' => $amount,
                'category' => 'PAY',
            ];
            try {
                $credential = $this->credentialService->getCredentials();
            } catch (ModelNotFoundException $e) {
                throw new ModelNotFoundException($e->getMessage());
            }
            $url = $credential->api_url.'/customers/'.strval($customerId).'/transactions/';
            try {
                $request = $this->api->post(
                    $url,
                    [
                        'body' => json_encode($postParams),
                        'headers' => [
                            'Content-Type' => 'application/json;charset=utf-8',
                            'Authorization' => 'Token '.$credential->authentication_token,
                        ],
                    ]
                );
                $transactionResult = json_decode((string) $request->getBody(), true);

                $manufacturerTransaction = $this->steamaTransaction->newQuery()->create([
                    'transaction_id' => $transactionResult['id'],
                    'site_id' => $transactionResult['site_id'],
                    'customer_id' => $transactionResult['customer_id'],
                    'amount' => $transactionResult['amount'],
                    'category' => $transactionResult['category'],
                    'provider' => $transactionResult['provider'] ?? 'AP',
                    'timestamp' => $transactionResult['timestamp'],
                    'synchronization_status' => $transactionResult['synchronization_status'],
                ]);

                $transactionContainer->transaction->originalTransaction()->first()->update([
                    'manufacturer_transaction_id' => $manufacturerTransaction->id,
                    'manufacturer_transaction_type' => 'steama_transaction',
                ]);
            } catch (SteamaApiResponseException $e) {
                Log::critical(
                    'Steama API Transaction Failed',
                    ['URL :' => $url, 'Body :' => json_encode($postParams), 'message :' => $e->getMessage()]
                );
                throw new SteamaApiResponseException($e->getMessage());
            }

            $token = $transactionResult['site_id'].'-'.
                $transactionResult['category'].'-'.
                $transactionResult['provider'].'-'.
                $transactionResult['customer_id'];

            return [
                'token' => $token,
                'token_type' => Token::TYPE_ENERGY,
                'token_unit' => Token::UNIT_KWH,
                'token_amount' => $transactionContainer->chargedEnergy,
            ];
        }
    }

    public function clearDevice(Device $device) {}
}
