<?php

namespace Inensus\SteamaMeter\Services;

use App\Events\PaymentSuccessEvent;
use App\Models\Token;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaMeter;
use Inensus\SteamaMeter\Models\SteamaTransaction;

class SteamaTransactionsService implements ISynchronizeService {
    private $stemaMeterService;
    private $steamaCustomerService;
    private $steamaCredentialService;
    private $steamaSiteService;
    private $steamaAgentService;
    private $steamaTransaction;
    private $steamaMeter;
    private $steamaApi;
    private $thirdPartyTransaction;
    private $rootUrl = '/transactions';
    private $transaction;
    private $token;
    private $steamaCustomer;
    private $steamaSyncSettingService;
    private $steamaSyncActionService;

    public function __construct(
        SteamaMeterService $steamaMeterService,
        SteamaCustomerService $steamaCustomerService,
        SteamaCredentialService $steamaCredentialService,
        SteamaSiteService $steamaSiteService,
        SteamaAgentService $steamaAgentService,
        SteamaTransaction $steamaTransaction,
        SteamaMeterApiClient $steamaApi,
        Transaction $transaction,
        SteamaMeter $steamaMeter,
        ThirdPartyTransaction $thirdPartyTransaction,
        Token $token,
        SteamaCustomer $steamaCustomer,
        SteamaSyncSettingService $steamaSyncSettingService,
        StemaSyncActionService $steamaSyncActionService,
    ) {
        $this->stemaMeterService = $steamaMeterService;
        $this->steamaCustomerService = $steamaCustomerService;
        $this->steamaCredentialService = $steamaCredentialService;
        $this->steamaSiteService = $steamaSiteService;
        $this->steamaAgentService = $steamaAgentService;
        $this->steamaTransaction = $steamaTransaction;
        $this->steamaApi = $steamaApi;
        $this->transaction = $transaction;
        $this->steamaMeter = $steamaMeter;
        $this->thirdPartyTransaction = $thirdPartyTransaction;
        $this->token = $token;
        $this->steamaCustomer = $steamaCustomer;
        $this->steamaSyncSettingService = $steamaSyncSettingService;
        $this->steamaSyncActionService = $steamaSyncActionService;
    }

    public function sync() {
        $synSetting = $this->steamaSyncSettingService->getSyncSettingsByActionName('Transactions');
        $syncAction = $this->steamaSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        $syncCheck = $this->syncCheck();
        if ($syncCheck['result']) {
            $lastCreatedTransaction = $this->steamaTransaction->newQuery()->latest('timestamp')->orderBy(
                'id',
                'desc'
            )->first();
            $lastRecordedTransactionId = 0;

            if ($lastCreatedTransaction) {
                $url = $this->rootUrl.'?ordering=timestamp&created_after='.
                    Carbon::parse($lastCreatedTransaction->timestamp)->toIso8601ZuluString().'&page=1&page_size=100';
                $lastRecordedTransactionId = $lastCreatedTransaction->transaction_id;
            } else {
                $url = $this->rootUrl.'?ordering=timestamp&created_before='.
                    Carbon::now()->toIso8601ZuluString().'&page=1&page_size=100';
            }
            $steamaMeters = $this->steamaMeter->newQuery()->with(['mpmMeter.device.person'])->get();
            try {
                $result = $this->steamaApi->get($url);
                $transactions = $result['results'];
                while ($result['next']) {
                    $transactionsCollection = collect($transactions);
                    $transactionsCollection->each(function ($transaction) use (
                        $steamaMeters,
                        $lastRecordedTransactionId
                    ) {
                        $steamaMeter = $steamaMeters->firstWhere('customer_id', $transaction['customer_id']);
                        if ($steamaMeter && $lastRecordedTransactionId < $transaction['id']) {
                            $steamaTransaction = $this->createSteamaTransaction($transaction);

                            if ($transaction['category'] == 'PAY') {
                                $thirdPartyTransaction = $this->createThirdPartyTransaction(
                                    $transaction,
                                    $steamaTransaction
                                );

                                $mainTransaction = $this->createTransaction(
                                    $transaction,
                                    $thirdPartyTransaction,
                                    $steamaMeter
                                );

                                $token = $this->createToken($steamaMeter, $mainTransaction, $transaction);

                                $this->createPayment($steamaMeter, $mainTransaction, $token);
                            }
                        }
                    });
                    $url = $this->rootUrl.'?'.explode('?', $result['next'])[1];
                    $result = $this->steamaApi->get($url);
                    $transactions = $result['results'];
                }
            } catch (SteamaApiResponseException $e) {
                $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, false);
                Log::critical('Transaction synchronising cancelled', ['message' => $e->getMessage()]);
                throw new SteamaApiResponseException($e->getMessage());
            }
        } else {
            Log::debug('Transaction synchronising cancelled', ['message' => $syncCheck['message']]);
        }
        $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, true);

        return $syncCheck['message'];
    }

    public function syncCheck() {
        $credentials = $this->steamaCredentialService->getCredentials();
        if ($credentials) {
            if ($credentials->is_authenticated) {
                $siteSynchronized = $this->steamaSiteService->syncCheck();

                if ($siteSynchronized['result']) {
                    $customerSynchronized = $this->steamaCustomerService->syncCheck();

                    if ($customerSynchronized['result']) {
                        $meterSynchronized = $this->stemaMeterService->syncCheck();

                        if ($meterSynchronized['result']) {
                            $agentSynchronized = $this->steamaAgentService->syncCheck();
                            if ($agentSynchronized['result']) {
                                return ['result' => true, 'message' => 'Records are updated'];
                            } else {
                                return ['result' => false, 'message' => 'Agent records are not up to date.'];
                            }
                        } else {
                            return ['result' => false, 'message' => 'Meter records are not up to date.'];
                        }
                    } else {
                        return ['result' => false, 'message' => 'Customer records are not up to date.'];
                    }
                } else {
                    return ['result' => false, 'message' => 'Site records are not up to date.'];
                }
            } else {
                return ['result' => false, 'message' => 'Credentials records are not up to date.'];
            }
        } else {
            return ['result' => false, 'message' => 'No Credentials record found.'];
        }
    }

    public function getTransactionsByCustomer($customer, $request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->steamaTransaction->newQuery()->where('customer_id', $customer)->paginate($perPage);
    }

    public function getSteamaTransactions($transactionMin) {
        return $this->steamaTransaction->newQuery()->with(['thirdPartyTransaction.transaction'])->where(
            'timestamp',
            '>=',
            Carbon::now()->subMinutes($transactionMin)
        )->where('category', 'PAY')->get();
    }

    private function createSteamaTransaction($transaction) {
        return $this->steamaTransaction->newQuery()->create([
            'transaction_id' => $transaction['id'],
            'site_id' => $transaction['site_id'],
            'customer_id' => $transaction['customer_id'],
            'amount' => $transaction['amount'],
            'category' => $transaction['category'],
            'provider' => $transaction['provider'] ?? 'AP',
            'timestamp' => $transaction['timestamp'],
            'synchronization_status' => $transaction['synchronization_status'],
        ]);
    }

    private function createThirdPartyTransaction($transaction, $steamaTransaction) {
        $thirdPartyTransaction = $this->thirdPartyTransaction->newQuery()->make([
            'transaction_id' => $transaction['id'],
            'status' => $transaction['reversed_by_id'] !== null ? -1 : 1,
            'description' => $transaction['provider'] === 'AA' ?
                'Payment recorded by agent : '.$transaction['agent_id'].' ~Steama Meter' : null,
        ]);
        $thirdPartyTransaction->manufacturerTransaction()->associate($steamaTransaction);
        $thirdPartyTransaction->save();

        return $thirdPartyTransaction;
    }

    private function createTransaction($transaction, $thirdPartyTransaction, $steamaMeter) {
        $transaction = $this->transaction->newQuery()->make([
            'amount' => (int) $transaction['amount'],
            'sender' => $transaction['customer_telephone'],
            'message' => $steamaMeter->mpmMeter->serial_number,
            'type' => 'energy',
            'created_at' => $transaction['timestamp'],
            'updated_at' => $transaction['timestamp'],
        ]);
        $transaction->originalTransaction()->associate($thirdPartyTransaction);
        $transaction->save();

        return $transaction;
    }

    private function createToken($steamaMeter, $mainTransaction, $transaction) {
        $stmCustomer = $steamaMeter->stmCustomer->first();
        $customerEnergyPrice = $stmCustomer->energy_price;
        $chargedEnergy = $mainTransaction->amount / $customerEnergyPrice;

        $token = $transaction['site_id'].'-'.
            $transaction['category'].'-'.
            $transaction['provider'].'-'.
            $transaction['customer_id'];

        $token = $this->token->newQuery()->where('transaction_id', $mainTransaction->id)->first();
        if (!$token) {
            $token = $this->token->newQuery()->make([
                'transaction_id' => $mainTransaction->id,
                'token' => $token,
                'load' => $chargedEnergy,
            ]);
            $token->save();
        }

        return $token;
    }

    private function createPayment($steamaMeter, $mainTransaction, $token) {
        $owner = $steamaMeter->mpmMeter->device()->person;

        if ($owner) {
            event(new PaymentSuccessEvent(
                amount: $mainTransaction->amount,
                paymentService: $mainTransaction->original_transaction_type,
                paymentType: 'energy',
                sender: $mainTransaction->sender,
                paidFor: $token,
                payer: $owner,
                transaction: $mainTransaction,
            ));
        }
    }
}
