<?php

namespace MPM\Transaction;

use App\Models\Asset;
use App\Models\EBike;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IAssociative<Transaction>
 * @implements IBaseService<Transaction>
 */
class TransactionService implements IAssociative, IBaseService {
    public const YESTERDAY = 0;
    public const SAME_DAY_LAST_WEEK = 1;
    public const LAST_SEVEN_DAYS = 2;
    public const LAST_THIRTY_DAYS = 3;

    public const PERCENTAGE_DIVIDER = 100;

    public function __construct(
        private Transaction $transaction,
        private MeterTransactionService $meterTransactionService,
        private SolarHomeSystemTransactionService $solarHomeSystemTransactionService,
        private ApplianceTransactionService $applianceTransactionService,
        private EBikeTransactionService $eBikeTransactionService,
    ) {}

    /**
     * @param array<int> $transactionIds
     */
    private function getTotalAmountOfConfirmedTransaction($transactionIds): float {
        return (float) $this->transaction->newQuery()->whereHasMorph(
            'originalTransaction',
            '*',
            static fn ($q) => $q->where('status', 1)
        )
            ->whereIn('id', $transactionIds)
            ->sum('amount');
    }

    /**
     * @param array<int> $transactionIds
     */
    private function getTransactionCountByStatus($transactionIds, bool $status): int {
        $status = $status === true ? 1 : 0;

        return $this->transaction->newQuery()->whereHasMorph(
            'originalTransaction',
            '*',
            static fn ($q) => $q->where('status', $status)
        )
            ->whereIn('id', $transactionIds)
            ->count();
    }

    private function getPercentage(int $base, int $wanted, bool $baseShouldGreater = true): float {
        if ($base === 0 || $wanted === 0) {
            return 0;
        }

        $percentage = (float) $wanted * self::PERCENTAGE_DIVIDER / (float) $base;

        if ($baseShouldGreater) {
            return round(100 - $percentage, 2);
        }

        return round($percentage - 100, 2);
    }

    public function getRelatedService(string $type): ApplianceTransactionService|MeterTransactionService|SolarHomeSystemTransactionService|EBikeTransactionService {
        switch ($type) {
            case SolarHomeSystem::RELATION_NAME:
                return $this->solarHomeSystemTransactionService;
            case Asset::RELATION_NAME:
                return $this->applianceTransactionService;
            case EBike::RELATION_NAME:
                return $this->eBikeTransactionService;
            default:
                return $this->meterTransactionService;
        }
    }

    /**
     * @return array<string, array<string, string>>|null
     */
    public function determinePeriod(int $period): ?array {
        $comparisonPeriod = null;
        switch ($period) {
            case self::YESTERDAY:
                $duration = new \DateInterval('P1D');
                $comparisonPeriod = [
                    'currentPeriod' => [
                        'begins' => (new \DateTime())->format('Y-m-d 00:00:00'),
                        'ends' => (new \DateTime())->format('Y-m-d 23:59:59'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new \DateTime())->sub($duration)->format('Y-m-d 00:00:00'),
                        'ends' => (new \DateTime())->sub($duration)->format('Y-m-d 23:59:59'),
                    ],
                ];
                break;
            case self::SAME_DAY_LAST_WEEK:
                $duration = new \DateInterval('P7D');
                $comparisonPeriod = [
                    'currentPeriod' => [
                        'begins' => (new \DateTime())->format('Y-m-d 00:00:00'),
                        'ends' => (new \DateTime())->format('Y-m-d 23:59:59'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new \DateTime())->sub($duration)->format('Y-m-d 00:00:00'),
                        'ends' => (new \DateTime())->sub($duration)->format('Y-m-d 23:59:59'),
                    ],
                ];
                break;
            case self::LAST_SEVEN_DAYS:
                $currentDuration = new \DateInterval('P7D');
                $lastDuration = new \DateInterval('P14D');
                $comparisonPeriod = [
                    'currentPeriod' => [
                        'begins' => (new \DateTime())->sub($currentDuration)->format('Y-m-d'),
                        'ends' => (new \DateTime())->format('Y-m-d'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new \DateTime())->sub($lastDuration)->format('Y-m-d'),
                        'ends' => (new \DateTime())->sub($currentDuration)->format('Y-m-d'),
                    ],
                ];
                break;
            case self::LAST_THIRTY_DAYS:
                $currentDuration = new \DateInterval('P30D');
                $lastDuration = new \DateInterval('P60D');
                $comparisonPeriod = [
                    'currentPeriod' => [
                        'begins' => (new \DateTime())->sub($currentDuration)->format('Y-m-d'),
                        'ends' => (new \DateTime())->format('Y-m-d'),
                    ],
                    'lastPeriod' => [
                        'begins' => (new \DateTime())->sub($lastDuration)->format('Y-m-d'),
                        'ends' => (new \DateTime())->sub($currentDuration)->format('Y-m-d'),
                    ],
                ];
                break;
        }

        return $comparisonPeriod;
    }

    /**
     * @param array<string, array<string, string>> $comparisonPeriod
     *
     * @return array<string, \Illuminate\Support\Collection<int, int>>
     */
    public function getByComparisonPeriod(array $comparisonPeriod): array {
        $currentTransactions = $this->transaction->newQuery()->whereBetween(
            'created_at',
            [
                $comparisonPeriod['currentPeriod']['begins'],
                $comparisonPeriod['currentPeriod']['ends'],
            ]
        )
            ->pluck('id');
        $pastTransactions = $this->transaction->newQuery()->whereBetween(
            'created_at',
            [
                $comparisonPeriod['lastPeriod']['begins'],
                $comparisonPeriod['lastPeriod']['ends'],
            ]
        )
            ->pluck('id');

        return [
            'current' => $currentTransactions,
            'past' => $pastTransactions,
        ];
    }

    /**
     * @param array<int> $transactionIds
     *
     * @return array<string, int|float>|null
     */
    public function getAnalysis($transactionIds): ?array {
        if (count($transactionIds) === 0) {
            return null;
        }

        $total = count($transactionIds);

        // the total amount of confirmed transactions
        $amount = $this->getTotalAmountOfConfirmedTransaction($transactionIds);
        // the number of confirmed transactions
        $confirmation = $this->getTransactionCountByStatus($transactionIds, true);
        // The number of cancelled transactions
        $cancellation = $this->getTransactionCountByStatus($transactionIds, false);

        $cancellationPercentage = $cancellation * self::PERCENTAGE_DIVIDER / $total;
        $confirmationPercentage = $confirmation * self::PERCENTAGE_DIVIDER / $total;

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
     * @return array<string, int|float>
     */
    public function getEmptyCompareResult(): array {
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
     * @param array<string, int|float> $currentTransactions
     * @param array<string, int|float> $pastTransactions
     *
     * @return array<string, array<string, float|string>>
     */
    public function comparePeriods(array $currentTransactions, array $pastTransactions): array {
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
     * @param array<string, mixed> $transactionData
     */
    public function make(array $transactionData): Transaction {
        return $this->transaction->newQuery()->make($transactionData);
    }

    public function save($transaction): bool {
        return $transaction->save();
    }

    public function getById(int $id): Transaction {
        return $this->transaction->newQuery()->with([
            'token',
            'originalTransaction',
            'originalTransaction.conflicts',
            'sms',
            'paymentHistories',
            'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])])->find($id);
    }

    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<Transaction>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->transaction->newQuery()->with(['originalTransaction'])->latest()->paginate($limit);
        }

        return $this->transaction->newQuery()->latest()->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Transaction {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): Transaction {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
