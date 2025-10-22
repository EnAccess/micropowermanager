<?php

namespace Inensus\SparkMeter\Services;

use App\Events\PaymentSuccessEvent;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Error;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\CredentialsNotFoundException;
use Inensus\SparkMeter\Exceptions\CredentialsNotUpToDateException;
use Inensus\SparkMeter\Exceptions\NoOnlineSiteRecordException;
use Inensus\SparkMeter\Exceptions\SitesNotUpToDateException;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmCredential;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmOrganization;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SmTariff;
use Inensus\SparkMeter\Models\SmTransaction;

class TransactionService {
    private string $rootUrl = '/transaction/';

    public function __construct(
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private CredentialService $sparkCredentialService,
        private SiteService $sparkSiteService,
        private CustomerService $sparkCustomerService,
        private MeterModelService $sparkMeterModelService,
        private SmTariff $sparkTariff,
        private SmSite $sparkSite,
        private TariffService $sparkTariffService,
        private SmTransaction $sparkTransaction,
        private SmOrganization $sparkOrganization,
        private ThirdPartyTransaction $thirdPartyTransaction,
        private Transaction $transaction,
        private SmCustomer $smCustomer,
        private SmSyncSettingService $smSyncSettingService,
        private SmSyncActionService $smSyncActionService,
        private Token $token,
    ) {}

    public function updateTransactionStatus(SmTransaction $smTransaction): void {
        try {
            $smTransactionResult = $this->sparkMeterApiRequests->getInfo(
                $this->rootUrl,
                $smTransaction->transaction_id,
                $smTransaction->site_id
            );
            $smStatus = $smTransactionResult['transaction']['status'];
        } catch (\Exception $e) {
            $smStatus = $e->getMessage();
            Log::critical('Updating SmTransaction status information failed.', ['Error :' => $e->getMessage()]);
        }
        $status = match ($smStatus) {
            'processed' => 1,
            'pending' => 0,
            'not-processed', 'error' => -1,
            default => 1,
        };

        $transaction = $this->transaction->newQuery()->whereHasMorph(
            'originalTransaction',
            '*'
        )->find($smTransaction['external_id']);

        if ($transaction && $transaction->originalTransaction instanceof AgentTransaction) {
            $transaction->originalTransaction->update([
                'status' => $status,
            ]);
        } elseif ($transaction && $transaction->originalTransaction instanceof ThirdPartyTransaction) {
            $transaction->originalTransaction->update([
                'status' => $status,
            ]);
        }

        $smTransaction->update([
            'status' => $smStatus,
        ]);
    }

    public function sync(): void {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('Transactions');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        $syncCheck = [];
        // TODO find a way for variety of error handling acts.
        try {
            $syncCheck = $this->syncCheck();
        } catch (CredentialsNotFoundException|CredentialsNotUpToDateException|SitesNotUpToDateException|NoOnlineSiteRecordException $exception) {
            Log::warning($exception->getMessage());
        }
        $lastCreatedTransaction = $this->sparkTransaction->newQuery()->latest('created_at')->orderBy(
            'id',
            'desc'
        )->first();
        $organization = $this->sparkOrganization->newQuery()->first();
        if (!$organization) {
            Log::info('Organization not found for Koios API');

            return;
        }
        $koiosUrl = '/organizations/'.$organization->organization_id.'/data/historical';
        $params = [
            'filters' => [
                'entity_types' => ['transactions'],
                'date_range' => [
                    'from' => $lastCreatedTransaction ?
                        $lastCreatedTransaction->timestamp : Carbon::now()->subYears(10)->toIso8601String(),
                ],
            ],
            'cursor' => null,
        ];
        try {
            $result = $this->sparkMeterApiRequests->postToKoios($koiosUrl, $params);
        } catch (SparkAPIResponseException $exception) {
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            throw new SparkAPIResponseException($exception->getMessage());
        }
        $params['cursor'] = $result['cursor'];
        $transactions = $result['results'];
        $count = $result['count'];
        $sparkCustomers = $this->smCustomer->newQuery()->with([
            'mpmPerson.devices.device',
            'mpmPerson.addresses',
        ])->get();
        $sparkTariffs = $this->sparkTariff->newQuery()->get();
        do {
            if ($count === 1) {
                break;
            }
            if (count($syncCheck) === 0) {
                break;
            }
            // @phpstan-ignore argument.templateType,argument.templateType
            collect($transactions)->filter(fn (array $transaction): bool => $transaction['type'] === 'transaction')->each(function (array $transaction) use ($syncCheck, $sparkCustomers, $sparkTariffs): true {
                $siteTransaction = $syncCheck->firstWhere('site_id', $transaction['site']);
                if (!$siteTransaction) {
                    return true;
                }
                $status = match ($transaction['state']) {
                    'processed' => 1,
                    'pending' => 0,
                    'reversed', 'error' => -1,
                    default => 1,
                };
                $transactionRecord = $this->sparkTransaction->newQuery()->where(
                    'transaction_id',
                    $transaction['transaction_id']
                )->first();

                if (!$transactionRecord) {
                    if (!array_key_exists('customer', $transaction['to'])) {
                        return true;
                    }
                    $sparkTransaction = $this->createSparkTransaction($transaction);
                    if (!$transaction['reference_id']) {
                        $thirdPartyTransaction = $this->createThirdPartyTransaction(
                            $transaction,
                            $sparkTransaction,
                            $status
                        );

                        $sparkCustomer = $sparkCustomers->firstWhere(
                            'customer_id',
                            $sparkTransaction->customer_id
                        );

                        if (!$sparkCustomer) {
                            return true;
                        }
                        $meter = $sparkCustomer->mpmPerson->devices[0]->device;
                        $mainTransaction = $this->createTransaction(
                            $transaction,
                            $thirdPartyTransaction,
                            $meter
                        );
                        $sparkTariff = $sparkTariffs->firstWhere(
                            'mpm_tariff_id',
                            $meter->tariff()->first()->id
                        );
                        $token = $this->createToken(
                            $sparkTariff,
                            $mainTransaction,
                            $transaction
                        );
                        $this->createPayment($meter, $mainTransaction, $token);
                    }
                } else {
                    $transactionRecord->update([
                        'status' => $transaction['state'],
                        'timestamp' => $transaction['state'] === 'processed' ?
                            $transaction['processed_timestamp'] : ($transaction['state'] === 'reversed' ?
                                $transaction['reversed_timestamp'] : $transaction['errored_timestamp']),
                    ]);
                    $thirdPartyTransaction = $this->thirdPartyTransaction->newQuery()->where(
                        'transaction_id',
                        $transaction['transaction_id']
                    )->first();
                    if ($thirdPartyTransaction) {
                        $thirdPartyTransaction->update([
                            'status' => $status,
                        ]);
                    }
                }
                usleep(100000);

                return true;
            });

            try {
                $result = $this->sparkMeterApiRequests->postToKoios($koiosUrl, $params);
                $params['cursor'] = $result['cursor'];
                $count = $result['count'];
                $transactions = $result['results'];
            } catch (SparkAPIResponseException $exception) {
                $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, false);
                throw new SparkAPIResponseException($exception->getMessage());
            }
        } while ($params['cursor'] && $count > 0);
        $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, true);
    }

    /**
     * @return Collection<int, SmSite>
     */
    public function syncCheck(): Collection {
        $credentials = $this->sparkCredentialService->getCredentials();
        $sparkSites = $this->sparkSite->newQuery()->where('is_authenticated', 1)->where('is_online', 1)->get();

        if (!$credentials instanceof SmCredential) {
            $message = 'No Credentials record found.';
            throw new CredentialsNotFoundException($message);
        }
        if ($credentials->is_authenticated == 0) {
            $message = 'Credentials records are not up to date.';
            throw new CredentialsNotUpToDateException($message);
        }
        $siteSynchronized = $this->sparkSiteService->syncCheck();

        if (!$siteSynchronized['result']) {
            $message = 'Site records are not up to date.';
            throw new SitesNotUpToDateException($message);
        }

        if (!$sparkSites->count()) {
            $message = 'No online Site record found.';
            throw new NoOnlineSiteRecordException($message);
        }

        return $sparkSites->filter(function ($site): bool {
            $meterModelSynchronized = $this->sparkMeterModelService->syncCheckBySite($site->site_id);
            $tariffSynchronized = $this->sparkTariffService->syncCheckBySite($site->site_id);
            $customerSynchronized = $this->sparkCustomerService->syncCheckBySite($site->site_id);

            return $meterModelSynchronized['result']
                && $tariffSynchronized['result']
                && $customerSynchronized['result'];
        });
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function createSparkTransaction(array $transaction): SmTransaction {
        return $this->sparkTransaction->newQuery()->create([
            'site_id' => $transaction['site'],
            'customer_id' => $transaction['to']['customer']['id'],
            'transaction_id' => $transaction['transaction_id'],
            'status' => $transaction['state'],
            'timestamp' => $transaction['created'],
            'external_id' => $transaction['external_id'],
        ]);
    }

    /**
     * @return Collection<int, SmTransaction>
     */
    public function getSparkTransactions(int $transactionMin): Collection {
        $transactions = $this->sparkTransaction->newQuery()->with(['thirdPartyTransaction.transaction'])->where(
            'status',
            'processed'
        )->get();

        return $transactions->filter(fn ($transaction): bool => Carbon::parse($transaction->timestamp) >= Carbon::now()->subMinutes($transactionMin));
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function createThirdPartyTransaction(array $transaction, SmTransaction $sparkTransaction, int $status): ThirdPartyTransaction {
        $thirdPartyTransaction = $this->thirdPartyTransaction->newQuery()->make([
            'transaction_id' => $transaction['transaction_id'],
            'status' => $status,
        ]);
        $thirdPartyTransaction->manufacturerTransaction()->associate($sparkTransaction);
        $thirdPartyTransaction->save();

        return $thirdPartyTransaction;
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function createTransaction(array $transaction, ThirdPartyTransaction $thirdPartyTransaction, Meter $meter): Transaction {
        $transaction = $this->transaction->newQuery()->make([
            'amount' => (int) $transaction['amount'],
            // FIXME: Variable $sparkCustomer on left side of ?? is never defined.
            // 'sender' => $sparkCustomer->mpmPerson->addresses[0]->phone ?? '-',
            'sender' => '-',
            'message' => $meter->serial_number,
            'type' => 'energy',
            'created_at' => $transaction['created'],
            'updated_at' => $transaction['created'],
        ]);

        $transaction->originalTransaction()->associate($thirdPartyTransaction);
        $transaction->save();

        return $transaction;
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function createToken(SmTariff $sparkTariff, Transaction $mainTransaction, array $transaction): Token {
        try {
            $tariff = $this->sparkTariffService->singleSync($sparkTariff);
        } catch (SparkAPIResponseException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        $chargedEnergy = (int) $transaction['amount'] / ($tariff->total_price / 100);

        $token = $this->token->newQuery()->where('transaction_id', $mainTransaction->id)->first();
        if (!$token) {
            $token = $this->token->newQuery()->make([
                'transaction_id' => $mainTransaction->id,
                'token' => $token,
                'token_type' => Token::TYPE_ENERGY,
                'token_unit' => Token::UNIT_KWH,
                'token_amount' => $chargedEnergy,
            ]);
            $token->save();
        }

        return $token;
    }

    private function createPayment(Meter $meter, Transaction $mainTransaction, Token $token): void {
        $owner = $meter->device->person;
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
