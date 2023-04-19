<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Lib\ITransactionProvider;
use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use App\Services\TransactionService;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\Request;

use function count;

/**
 * @group   Transactions
 * Class TransactionController
 * @package App\Http\Controllers
 */
class TransactionController extends Controller
{
    public const YESTERDAY = 0;
    public const SAME_DAY_LAST_WEEK = 1;
    public const LAST_SEVEN_DAYS = 2;
    public const LAST_THIRTHY_DAYS = 3;


    /**
     * TransactionController constructor.
     *
     * @param Transaction $transaction
     * @param TransactionDataContainer $container
     */
    public function __construct(
        private Transaction $transaction,
        private TransactionDataContainer $container,
        private TransactionService $transactionService,
    )
    {
        $this->transaction = $transaction;
        $this->container = $container;
    }

    /**
     * List
     * The latest transactions.
     * The result is paginated and contains 15 results on default
     *
     * @urlParam     per_page int
     * @responseFile responses/transactions/transactions.list.json
     *
     * @return ApiResource
     */
    public function index(): ApiResource
    {
        $per_page = \request()->get('per_page') ?? 15;
        $transactions = Transaction::with('originalTransaction')->latest()->paginate($per_page);
        return new ApiResource($transactions);
    }

    /**
     * Basic Search
     * Searches in the transaction list for a match
     * The search term will be search in following fields;
     * - Sender/ Phone number
     * - Send message
     *
     * @bodyParam    term string required
     * @responseFile responses/transactions/transactions.search.json
     * @return       ApiResource
     */
    public function search()
    {
        $term = request('term');
        $transactions = Transaction::with('originalTransaction')->where(
            'sender',
            'LIKE',
            '%' . $term . '%'
        )->orWhere(
            'message',
            'LIKE',
            '%' . $term . '%'
        )->latest()->paginate(15);
        return new ApiResource($transactions);
    }

    /**
     * Search
     * Searches in the transaction list for a match
     * The search term will be search in following fields;
     * - Sender/ Phone number
     * - Send message
     * - Tariff
     * - Payment provider
     * - Status wether Cancelled or Approved
     * - Status wether Cancelled or Approved
     * - From
     * - To
     *
     * ** Terms can contain one or all of the following variables**
     * - serial_number string
     * - tariff int
     * - tariff string
     * - provider string
     * - status int
     * - from string
     * - to string
     *
     * @bodyParam terms JSON required
     *
     * @responseFile responses/transactions/transactions.search.json
     * @return       ApiResource
     */
    public function searchAdvanced()
    {

        $whereApplied = false;
        $per_page = (int)(request('per_page') ?? '15');

        $search = Transaction::with('originalTransaction');

        if (request('serial_number')) {
            $search->where('message', 'LIKE', '%' . request('serial_number') . '%');
            $whereApplied = true;
        }
        if (request('tariff')) {
            $tariff = request('tariff');
            if ($whereApplied) {
                $search->orWhereHas(
                    'token',
                    function ($q) use ($tariff) {
                        $q->whereHas(
                            'meter',
                            function ($q) use ($tariff) {
                                $q->whereHas(
                                    'meterParameter',
                                    function ($q) use ($tariff) {
                                        $q->whereHas(
                                            'tariff',
                                            function ($q) use ($tariff) {
                                                $q->where('id', $tariff);
                                            }
                                        );
                                    }
                                );
                            }
                        );
                    }
                );
            } else {
                $whereApplied = true;
                $search->whereHas(
                    'token',
                    function ($q) use ($tariff) {
                        $q->whereHas(
                            'meter',
                            function ($q) use ($tariff) {
                                $q->whereHas(
                                    'meterParameter',
                                    function ($q) use ($tariff) {
                                        $q->whereHas(
                                            'tariff',
                                            function ($q) use ($tariff) {
                                                $q->where('id', $tariff);
                                            }
                                        );
                                    }
                                );
                            }
                        );
                    }
                );
            }
        }
        if (request('provider')) {
            $search->with(request('provider'))->where(
                function ($q) {
                    $q->whereHas(
                        request('provider'),
                        function ($q) {
                            $q->whereNotNull('id');
                        }
                    );
                }
            );
        }
        if (request('status')) {
            $status = request('status');
            if (request('provider') && request('provider') !== '-1') {
                $search->where(
                    function ($q) use ($status) {
                        $q->whereHas(
                            request('provider'),
                            function ($q) use ($status) {
                                $q->where('status', $status);
                            }
                        );
                    }
                );
            } else {
                $search->whereHasMorph(
                    'originalTransaction',
                    '*',
                    function ($q) use ($status) {
                        $q->where('status', $status);
                    }
                )->get();
            }
        }
        if (request('from')) {
            $search->where('created_at', '>=', request('from'));
        }
        if (request('to')) {
            $search->where('created_at', '<=', request('to'));
        }
        $transactions = $search->latest()->paginate($per_page);

        return new ApiResource($transactions);
    }


    /**
     * Confirmed List
     * A list of confirmed transactions
     *
     * @responseFile responses/transactions/transactions.confirmed.list.json
     *
     * @return ApiResource
     */
    public function confirmed(): ApiResource
    {

        $transactions = Transaction::with('originalAirtel', 'originalVodacom')
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )->latest()->paginate();
        return new ApiResource($transactions);
    }


    /**
     * Cancelled List
     * A list of cancelled transactions
     *
     * @responseFile responses/transactions/transactions.cancelled.list.json
     * @return       ApiResource
     */
    public function cancelled(): ApiResource
    {
        $transactions = Transaction::with('originalAirtel', 'originalVodacom')
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', -1);
                }
            )->latest()->paginate();
        return new ApiResource($transactions);
    }


    /**
     * Create
     * Transactions can only been  created  by allowed IPS.
     * Please be sure to allow the mobile money provider IP under /config/services.php
     *
     * @param Request $request
     */
    public function store(Request $request): void
    {
        /**
         * @var ITransactionProvider
         */
        $transactionProvider = $request->attributes->get('transactionProcessor');
        // save main transaction
        $transactionProvider->saveTransaction();
        // store common data
        $transaction = $transactionProvider->saveCommonData();
        //fire transaction.saved -> confirms the transaction
        event('transaction.saved', $transactionProvider);

        ProcessPayment::dispatch($transaction->id)
            ->allOnConnection('redis')
            ->onQueue(config('services.queues.payment'));
    }


    /**
     * Detail
     * Shows the detail of the transaction with following relations
     * - Sent sms to the customer (if confirmed)
     * - Original Transaction source ( which mobile money provider)
     * - Generated token
     * - Payment history
     *
     * @urlParam     id int required
     * @responseFile responses/transactions/transaction.detail.json
     *
     * @param int $id
     *
     * @return ApiResource
     */
    public function show(int $id): ApiResource
    {
        $transaction = $this->transactionService->findById($id);
        return  ApiResource::make($transaction);
    }


    /**
     * Compares two given periods with each other
     *
     * @urlParam period required 0 for Yesterday, 1 for same day last week, 2 for last 7 days, 3 for last 30 days
     *
     * @param $period int is one of following;
     *                   Yesterday = 0,
     *                   Same day last week =1,
     *                   Last 7 days =2
     *                   Last 30 days =3
     *
     * @return array
     *
     * @throws Exception
     *
     * @responseFile responses/transactions/transactions.compare.json
     *
     */
    public function compare($period): array
    {

        $comparisionPeriod = $this->determinePeriod($period);

        //get transactions for both current and previous periods
        $transactions = $this->getTransactions($comparisionPeriod);


        // get data for the current period
        $currentTransactions = $this->getTransactionAnalysis($transactions['current']) ?? $this->emptyCompareResult();
        // get data for the previous period
        $pastTransactions = $this->getTransactionAnalysis($transactions['past']) ?? $this->emptyCompareResult();

        //compare current period with the previous period
        return [
            'success' => true,
            'current' => $currentTransactions,
            'past' => $pastTransactions,
            'analytics' => $this->comparePeriods($currentTransactions, $pastTransactions),
        ];
    }

    /**
     * @return array
     */
    private function emptyCompareResult(): array
    {
        return [
            'total' => 0,
            'amount' => 0,
            'confirmed' => 0,
            'confirmedPercentage' => 0,
            'cancelled' => 0,
            'cancelledPercentage' => 0,
        ];
    }

    /**
     * Collects the primary key for the periods
     *
     * @param array $comparisionPeriod
     *
     * @return array
     *
     */
    private function getTransactions(array $comparisionPeriod): array
    {

        $currentTransactions = Transaction::whereBetween(
            'created_at',
            [
                $comparisionPeriod['currentPeriod']['begins'],
                $comparisionPeriod['currentPeriod']['ends'],
            ]
        )
            ->pluck('id');
        $pastTransactionons = Transaction::whereBetween(
            'created_at',
            [
                $comparisionPeriod['lastPeriod']['begins'],
                $comparisionPeriod['lastPeriod']['ends'],
            ]
        )
            ->pluck('id');
        return [
            'current' => $currentTransactions,
            'past' => $pastTransactionons,
        ];
    }

    /**
     * Calculates the amount of cancelled transactions for the given ids
     *
     * @param  $transactionIds
     * @return mixed
     */
    private function getCancelledTransactions($transactionIds)
    {
        return Transaction::query()->whereHasMorph(
            'originalTransaction',
            '*',
            static function ($q) {
                $q->where('status', -1);
            }
        )
            ->whereIn('id', $transactionIds)
            ->count();
    }

    /**
     * Calculates the amount of confirmed transactions for the given ids
     *
     * @param  $transactionIds
     * @return mixed
     */
    private function getConfirmedTransactions($transactionIds)
    {
        return Transaction::query()->whereHasMorph(
            'originalTransaction',
            '*',
            static function ($q) {
                $q->where('status', 1);
            }
        )
            ->whereIn('id', $transactionIds)
            ->count();
    }

    /**
     * Calculates the summary of confirmed transactions based on the given ids
     *
     * @param  $transactionIds
     * @return mixed
     */
    private function getAmountOfConfirmedTransaction($transactionIds)
    {
        return Transaction::query()->whereHasMorph(
            'originalTransaction',
            '*',
            static function ($q) {
                $q->where('status', 1);
            }
        )
            ->whereIn('id', $transactionIds)
            ->sum('amount');
    }

    /**
     * Calculates the basic information for the given period
     *
     * @param $transactions
     *
     * @return array|null
     *
     */
    private function getTransactionAnalysis($transactions): ?array
    {

        if (count($transactions) === 0) {
            return null;
        }

        $total = count($transactions);

        // the total amount of confirmed transactions
        $amount = $this->getAmountOfConfirmedTransaction($transactions);
        // the number of confirmed transactions
        $confirmation = $this->getConfirmedTransactions($transactions);
        // The number of cancelled transactions
        $cancellation = $this->getCancelledTransactions($transactions);


        $cancellationPercentage = $cancellation * 100 / $total;
        $confirmationPercentage = $confirmation * 100 / $total;

        return [
            'total' => $total,
            'amount' => $amount,
            'confirmed' => $confirmation,
            'confirmedPercentage' => $confirmationPercentage,
            'cancelled' => $cancellation,
            'cancelledPercentage' => $cancellationPercentage,
        ];
    }

    /**
     * Compares two period data's with each other and calculates the percentage of their data
     *
     * @param array $currentTransactions
     * @param array $pastTransactions
     *
     * @return array
     *
     */
    private function comparePeriods(array $currentTransactions, array $pastTransactions): array
    {
        $totalPercentage = $this->getPercentage($pastTransactions['total'], $currentTransactions['total'], false);
        $confirmationPercentage = round(
            $currentTransactions['confirmedPercentage'] - $pastTransactions['confirmedPercentage'],
            2
        );
        $cancellationPercentage = round(
            $currentTransactions['cancelledPercentage'] - $pastTransactions['cancelledPercentage'],
            2
        );
        $amountPercentage = $this->getPercentage($pastTransactions['amount'], $currentTransactions['amount'], false);
        return [
            'totalPercentage' => [
                'percentage' => $totalPercentage,
                'color' => $totalPercentage >= 0 ? 'green' : 'red',
            ],
            'confirmationPercentage' => [
                'percentage' => $confirmationPercentage,
                'color' => $confirmationPercentage >= 0 ? 'green' : 'red',
            ],
            'cancelationPercentage' => [
                'percentage' => $cancellationPercentage,
                'color' => $cancellationPercentage > 0 ? 'red' : 'green',
            ],
            'amountPercentage' => [
                'percentage' => $amountPercentage,
                'color' => $amountPercentage >= 0 ? 'green' : 'red',
            ],
        ];
    }

    /**
     * @param $base
     * @param $wanted
     * @param bool $baseShouldGreater if the base data should be greater than the wanted
     *
     * @return float
     */
    private function getPercentage($base, $wanted, bool $baseShouldGreater = true): float
    {

        if ($base === 0 || $wanted === 0) {
            return 0;
        }

        $percentage = (float)$wanted * 100 / (float)$base;

        if ($baseShouldGreater) {
            return round(100 - $percentage, 2);
        }

        return round($percentage - 100, 2);
    }

    /**
     * Determines the beginning and the ending date-ranges
     * which are going to be used for the transaction dasbhoard overview
     *
     * @param $period
     *
     * @return array|null
     *
     * @throws Exception
     *
     */
    private function determinePeriod($period): ?array
    {
        $comparisionPeriod = null;
        switch ($period) {
            case self::YESTERDAY:
                $duration = new DateInterval('P1D');
                $comparisionPeriod = [
                    'currentPeriod' => [
                        'begins' => (new DateTime())->format('Y-m-d 00:00:00'),
                        'ends' => (new DateTime())->format('Y-m-d 23:59:59'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new DateTime())->sub($duration)->format('Y-m-d 00:00:00'),
                        'ends' => (new DateTime())->sub($duration)->format('Y-m-d 23:59:59'),
                    ],
                ];
                break;
            case self::SAME_DAY_LAST_WEEK:
                $duration = new DateInterval('P7D');
                $comparisionPeriod = [
                    'currentPeriod' => [
                        'begins' => (new DateTime())->format('Y-m-d 00:00:00'),
                        'ends' => (new DateTime())->format('Y-m-d 23:59:59'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new DateTime())->sub($duration)->format('Y-m-d 00:00:00'),
                        'ends' => (new DateTime())->sub($duration)->format('Y-m-d 23:59:59'),
                    ],
                ];
                break;
            case self::LAST_SEVEN_DAYS:
                $currentDuration = new DateInterval('P7D');
                $lastDuration = new DateInterval('P14D');
                $comparisionPeriod = [
                    'currentPeriod' => [
                        'begins' => (new DateTime())->sub($currentDuration)->format('Y-m-d'),
                        'ends' => (new DateTime())->format('Y-m-d'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new DateTime())->sub($lastDuration)->format('Y-m-d'),
                        'ends' => (new DateTime())->sub($currentDuration)->format('Y-m-d'),
                    ],
                ];
                break;
            case self::LAST_THIRTHY_DAYS:
                $currentDuration = new DateInterval('P30D');
                $lastDuration = new DateInterval('P60D');
                $comparisionPeriod = [
                    'currentPeriod' => [
                        'begins' => (new DateTime())->sub($currentDuration)->format('Y-m-d'),
                        'ends' => (new DateTime())->format('Y-m-d'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new DateTime())->sub($lastDuration)->format('Y-m-d'),
                        'ends' => (new DateTime())->sub($currentDuration)->format('Y-m-d'),
                    ],
                ];
                break;
        }

        return $comparisionPeriod;
    }
}
