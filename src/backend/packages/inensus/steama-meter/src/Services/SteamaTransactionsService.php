<?php

namespace Inensus\SteamaMeter\Services;

use App\Events\PaymentSuccessEvent;
use App\Models\Token;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaCredential;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaMeter;
use Inensus\SteamaMeter\Models\SteamaTransaction;

/**
 * @implements ISynchronizeService<SteamaTransaction>
 */
class SteamaTransactionsService implements ISynchronizeService {
    private string $rootUrl = '/transactions';

    public function __construct(
        private SteamaMeterService $stemaMeterService,
        private SteamaCustomerService $steamaCustomerService,
        private SteamaCredentialService $steamaCredentialService,
        private SteamaSiteService $steamaSiteService,
        private SteamaAgentService $steamaAgentService,
        private SteamaTransaction $steamaTransaction,
        private SteamaMeterApiClient $steamaApi,
        private Transaction $transaction,
        private SteamaMeter $steamaMeter,
        private ThirdPartyTransaction $thirdPartyTransaction,
        private Token $token,
        private SteamaSyncSettingService $steamaSyncSettingService,
        private StemaSyncActionService $steamaSyncActionService,
    ) {}

    public function sync(): LengthAwarePaginator {
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
                    // @phpstan-ignore argument.templateType, argument.templateType
                    $transactionsCollection = collect($transactions);
                    $transactionsCollection->each(function (array $transaction) use (
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

                                $token = $this->createToken($steamaMeter, $mainTransaction);

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

    /**
     * @return array<string, mixed>
     */
    public function syncCheck(bool $returnData = false): array {
        $credentials = $this->steamaCredentialService->getCredentials();
        if ($credentials instanceof SteamaCredential) {
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

    /**
     * @return LengthAwarePaginator<int, SteamaTransaction>
     */
    public function getTransactionsByCustomer(SteamaCustomer $customer, Request $request): LengthAwarePaginator {
        $perPage = (int) $request->input('per_page', 15);

        return $this->steamaTransaction->newQuery()->where('customer_id', $customer)->paginate($perPage);
    }

    /**
     * @return Collection<int, SteamaTransaction>
     */
    public function getSteamaTransactions(int $transactionMin): Collection {
        return $this->steamaTransaction->newQuery()->with(['thirdPartyTransaction.transaction'])->where(
            'timestamp',
            '>=',
            Carbon::now()->subMinutes($transactionMin)
        )->where('category', 'PAY')->get();
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function createSteamaTransaction(array $transaction): SteamaTransaction {
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

    /**
     * @param array<string, mixed> $transaction
     */
    private function createThirdPartyTransaction(array $transaction, SteamaTransaction $steamaTransaction): ThirdPartyTransaction {
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

    /**
     * @param array<string, mixed> $transaction
     */
    private function createTransaction(array $transaction, ThirdPartyTransaction $thirdPartyTransaction, SteamaMeter $steamaMeter): Transaction {
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

    private function createToken(SteamaMeter $steamaMeter, Transaction $mainTransaction): Token {
        $stmCustomer = $steamaMeter->stmCustomer->first();
        $customerEnergyPrice = $stmCustomer->energy_price;
        $chargedEnergy = $mainTransaction->amount / $customerEnergyPrice;

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

    private function createPayment(SteamaMeter $steamaMeter, Transaction $mainTransaction, Token $token): void {
        $owner = $steamaMeter->mpmMeter->device->person;

        if ($owner) {
            event(new PaymentSuccessEvent(
                amount: (int) $mainTransaction->amount,
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
