<?php

namespace Inensus\SparkMeter\Services;

use App\Events\PaymentSuccessEvent;
use App\Models\Token;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\CredentialsNotFoundException;
use Inensus\SparkMeter\Exceptions\CredentialsNotUpToDateException;
use Inensus\SparkMeter\Exceptions\NoOnlineSiteRecordException;
use Inensus\SparkMeter\Exceptions\SitesNotUpToDateException;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmOrganization;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SmTariff;
use Inensus\SparkMeter\Models\SmTransaction;

class TransactionService {
    private $sparkMeterApiRequests;
    private $sparkOrganization;
    private $sparkCredentialService;
    private $sparkSiteService;
    private $sparkCustomerService;
    private $sparkMeterModelService;
    private $sparkTariffService;
    private $sparkTransaction;
    private $sparkTariff;
    private $thirdPartyTransaction;
    private $transaction;
    private $sparkSite;
    private $smCustomer;
    private $rootUrl = '/transaction/';
    private $smSyncSettingService;
    private $smSyncActionService;
    private Token $token;

    public function __construct(
        SparkMeterApiRequests $sparkMeterApiRequests,
        CredentialService $sparkCredentialService,
        SiteService $sparkSiteService,
        CustomerService $sparkCustomerService,
        MeterModelService $sparkMeterModelService,
        SmTariff $sparkTariff,
        SmSite $sparkSite,
        TariffService $sparkTariffService,
        SmTransaction $sparkTransaction,
        SmOrganization $sparkOrganization,
        ThirdPartyTransaction $thirdPartyTransaction,
        Transaction $transaction,
        SmCustomer $smCustomer,
        SmSyncSettingService $smSyncSettingService,
        SmSyncActionService $smSyncActionService,
        Token $token,
    ) {
        $this->sparkMeterApiRequests = $sparkMeterApiRequests;
        $this->sparkOrganization = $sparkOrganization;
        $this->sparkCredentialService = $sparkCredentialService;
        $this->sparkSiteService = $sparkSiteService;
        $this->sparkCustomerService = $sparkCustomerService;
        $this->sparkTariff = $sparkTariff;
        $this->sparkSite = $sparkSite;
        $this->sparkMeterModelService = $sparkMeterModelService;
        $this->sparkTariffService = $sparkTariffService;
        $this->sparkTransaction = $sparkTransaction;
        $this->thirdPartyTransaction = $thirdPartyTransaction;
        $this->transaction = $transaction;
        $this->smCustomer = $smCustomer;
        $this->smSyncSettingService = $smSyncSettingService;
        $this->smSyncActionService = $smSyncActionService;
        $this->token = $token;
    }

    public function updateTransactionStatus($smTransaction) {
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
        switch ($smStatus) {
            case 'processed':
                $status = 1;
                break;
            case 'pending':
                $status = 0;
                break;
            case 'not-processed':
            case 'error':
                $status = -1;
                break;
            default:
                $status = 1;
        }

        $transaction = $this->transaction->newQuery()->whereHasMorph(
            'originalTransaction',
            '*'
        )->find($smTransaction['external_id']);

        if ($transaction && $transaction->orginalAgent) {
            $transaction->orginalAgent->update([
                'status' => $status,
            ]);
        } else {
            if ($transaction && $transaction->originalThirdParty) {
                $transaction->originalThirdParty->update([
                    'status' => $status,
                ]);
            }
        }

        $smTransaction->update([
            'status' => $smStatus,
        ]);
    }

    public function sync() {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('Transactions');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        $syncCheck = [];
        // TODO find a way for variety of error handling acts.
        try {
            $syncCheck = $this->syncCheck();
        } catch (CredentialsNotFoundException $exception) {
            Log::warning($exception->getMessage());
        } catch (CredentialsNotUpToDateException $exception) {
            Log::warning($exception->getMessage());
        } catch (SitesNotUpToDateException $exception) {
            Log::warning($exception->getMessage());
        } catch (NoOnlineSiteRecordException $exception) {
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
            'mpmPerson.meters.meter',
            'mpmPerson.addresses',
        ])->get();
        $sparkTariffs = $this->sparkTariff->newQuery()->get();
        do {
            if ($count === 1) {
                break;
            }
            if (!count($syncCheck)) {
                break;
            }
            collect($transactions)->filter(function ($transaction) {
                return $transaction['type'] === 'transaction';
            })->each(function ($transaction) use ($syncCheck, $sparkCustomers, $sparkTariffs) {
                $siteTransaction = $syncCheck->firstWhere('site_id', $transaction['site']);
                if (!$siteTransaction) {
                    return true;
                }
                switch ($transaction['state']) {
                    case 'processed':
                        $status = 1;
                        break;
                    case 'pending':
                        $status = 0;
                        break;
                    case 'reversed':
                    case 'error':
                        $status = -1;
                        break;
                    default:
                        $status = 1;
                }
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
                        $meter = $sparkCustomer->mpmPerson->meters[0];
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
                            $transaction,
                            $sparkTransaction,
                            $meter
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

    public function syncCheck() {
        $credentials = $this->sparkCredentialService->getCredentials();
        $sparkSites = $this->sparkSite->newQuery()->where('is_authenticated', 1)->where('is_online', 1)->get();

        if (!$credentials) {
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

        return $sparkSites->filter(function ($site) {
            $meterModelSynchronized = $this->sparkMeterModelService->syncCheckBySite($site->site_id);
            $tariffSynchronized = $this->sparkTariffService->syncCheckBySite($site->site_id);
            $customerSynchronized = $this->sparkCustomerService->syncCheckBySite($site->site_id);

            return $meterModelSynchronized['result']
                && $tariffSynchronized['result']
                && $customerSynchronized['result'];
        });
    }

    private function createSparkTransaction($transaction) {
        return $this->sparkTransaction->newQuery()->create([
            'site_id' => $transaction['site'],
            'customer_id' => $transaction['to']['customer']['id'],
            'transaction_id' => $transaction['transaction_id'],
            'status' => $transaction['state'],
            'timestamp' => $transaction['created'],
            'external_id' => $transaction['external_id'],
        ]);
    }

    public function getSparkTransactions($transactionMin) {
        $transactions = $this->sparkTransaction->newQuery()->with(['thirdPartyTransaction.transaction'])->where(
            'status',
            'processed'
        )->get();

        return $transactions->filter(function ($transaction) use ($transactionMin) {
            return Carbon::parse($transaction->timestamp) >= Carbon::now()->subMinutes($transactionMin);
        });
    }

    private function createThirdPartyTransaction($transaction, $sparkTransaction, $status) {
        $thirdPartyTransaction = $this->thirdPartyTransaction->newQuery()->make([
            'transaction_id' => $transaction['transaction_id'],
            'status' => $status,
        ]);
        $thirdPartyTransaction->manufacturerTransaction()->associate($sparkTransaction);
        $thirdPartyTransaction->save();

        return $thirdPartyTransaction;
    }

    private function createTransaction($transaction, $thirdPartyTransaction, $meter) {
        $transaction = $this->transaction->newQuery()->make([
            'amount' => (int) $transaction['amount'],
            'sender' => $sparkCustomer->mpmPerson->addresses[0]->phone ?? '-',
            'message' => $meter->serial_number,
            'type' => 'energy',
            'created_at' => $transaction['created'],
            'updated_at' => $transaction['created'],
        ]);

        $transaction->originalTransaction()->associate($thirdPartyTransaction);
        $transaction->save();

        return $transaction;
    }

    private function createToken($sparkTariff, $mainTransaction, $transaction, $sparkTransaction, $meterParameter) {
        try {
            $tariff = $this->sparkTariffService->singleSync($sparkTariff);
        } catch (SparkAPIResponseException $exception) {
            throw new SparkAPIResponseException($exception->getMessage());
        }

        $chargedEnergy = (int) $transaction['amount'] / ($tariff->total_price / 100);

        $token = $sparkTransaction->site_id.'-'.$transaction['source'].'-'.$sparkTransaction->customer_id;

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

    private function createPayment($meter, $mainTransaction, $token) {
        $owner = $meter->device()->person;
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
