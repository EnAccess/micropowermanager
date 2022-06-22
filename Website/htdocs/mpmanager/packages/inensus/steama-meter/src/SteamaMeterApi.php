<?php

namespace Inensus\SteamaMeter;

use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaTransaction;
use Inensus\SteamaMeter\Services\SteamaCredentialService;
use Inensus\SteamaMeter\Services\SteamaCustomerService;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;

class SteamaMeterApi implements IManufacturerAPI
{
    protected $api;
    private $meterParameter;
    private $steamaCustomer;
    private $credentialService;
    private $customerService;
    private $steamaTransaction;
    private $transaction;
    public function __construct(
        Client $httpClient,
        MeterParameter $meterParameter,
        SteamaCustomer $steamaCustomer,
        SteamaCredentialService $credentialService,
        SteamaCustomerService $customerService,
        SteamaTransaction $steamaTransaction,
        Transaction $transaction
    ) {
        $this->api = $httpClient;
        $this->meterParameter = $meterParameter;
        $this->steamaCustomer = $steamaCustomer;
        $this->credentialService = $credentialService;
        $this->customerService = $customerService;
        $this->steamaTransaction = $steamaTransaction;

        $this->transaction = $transaction;
    }

    public function chargeMeter(TransactionDataContainer $transactionContainer): array
    {
        $meterParameter = $this->meterParameter->newQuery()->with('owner')->where(
            'id',
            $transactionContainer->meterParameter->id
        )->firstOrFail();

        $stmCustomer = $this->steamaCustomer->newQuery()->with('paymentPlans')->where(
            'mpm_customer_id',
            $meterParameter->owner->id
        )->first();

        $customerId = $stmCustomer->customer_id;
        $stmCustomer = $this->customerService->syncTransactionCustomer($stmCustomer->id);
        $customerEnergyPrice = $stmCustomer->energy_price;
        $transactionContainer->chargedEnergy += $transactionContainer->amount / ($customerEnergyPrice);
        if (config('app.debug')) {
            return [
                'token' => 'debug-token',
                'energy' => (float)$transactionContainer->chargedEnergy,
            ];
        } else {
            $amount = $transactionContainer->totalAmount;
            $postParams = [
                'amount' => $amount,
                'category' => 'PAY'
            ];
            try {
                $credential = $this->credentialService->getCredentials();
            } catch (ModelNotFoundException $e) {
                throw new ModelNotFoundException($e->getMessage());
            }
            $url = $credential->api_url . '/customers/' . strval($customerId) . '/transactions/';
            try {
                $request = $this->api->post(
                    $url,
                    [
                        'body' => json_encode($postParams),
                        'headers' => [
                            'Content-Type' => 'application/json;charset=utf-8',
                            'Authorization' => 'Token ' . $credential->authentication_token
                        ],
                    ]
                );
                $transactionResult = json_decode((string)$request->getBody(), true);
                $this->associateManufacturerTransaction($transactionContainer, $transactionResult);
            } catch (SteamaApiResponseException $e) {
                Log::critical(
                    'Steama API Transaction Failed',
                    ['URL :' => $url, 'Body :' => json_encode($postParams), 'message :' => $e->getMessage()]
                );
                throw new SteamaApiResponseException($e->getMessage());
            }

            $token = $transactionResult['site_id'] . '-' .
                $transactionResult['category'] . '-' .
                $transactionResult['provider'] . '-' .
                $transactionResult['customer_id'];
            return [
                'token' => $token,
                'energy' => $transactionContainer->chargedEnergy
            ];
        }
    }

    public function clearMeter(Meter $meter)
    {
    }

    public function associateManufacturerTransaction(
        TransactionDataContainer $transactionContainer,
        $transactionResult = []
    ) {
        $manufacturerTransaction = $this->steamaTransaction->newQuery()->create([
            'transaction_id' => $transactionResult['id'],
            'site_id' => $transactionResult['site_id'],
            'customer_id' => $transactionResult['customer_id'],
            'amount' => $transactionResult['amount'],
            'category' => $transactionResult['category'],
            'provider' => $transactionResult['provider'] ?? 'AP',
            'timestamp' => $transactionResult['timestamp'],
            'synchronization_status' => $transactionResult['synchronization_status']
        ]);
        $transactionContainer->transaction->originalTransaction()->associate($manufacturerTransaction)->save();
    }
}
