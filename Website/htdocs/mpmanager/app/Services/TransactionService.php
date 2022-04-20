<?php



namespace App\Services;

use App\Http\Middleware\Transaction;
use App\Models\Meter\Meter;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\VodacomTransaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    /**
     * @var Transaction
     */
    private $transaction;
    /**
     * @var Meter
     */
    private $meter;

    /**
     * TransactionService constructor.
     *
     * @param Transaction $transaction
     * @param Meter $meter
     */
    public function __construct(Transaction $transaction, Meter $meter)
    {
        $this->transaction = $transaction;
        $this->meter = $meter;
    }

    /**
     * @param $meters Collection :Model Collection
     *
     * @param $range
     *
     * @return int
     */
    public function totalMeterTransactions(Collection $meters, $range): int
    {
        $total = $this->meter->sumOfTransactions($meters->pluck('serial_number')->toArray(), $range);
        return $total[0]['total'] ?? 0;
    }

    /**
     * @param (mixed|string)[] $range
     * @param array $range
     * @return int|mixed
     */
    public function totalClusterTransactions($clusterId, array $range)
    {

        return   \App\Models\Transaction\Transaction::query()->whereHas(
            'meter',
            function ($q) use ($clusterId) {
                $q->whereHas(
                    'meterParameter',
                    function ($q) use ($clusterId) {
                        $q->whereHas(
                            'address',
                            function ($q) use ($clusterId) {
                                $q->whereHas(
                                    'city',
                                    function ($q) use ($clusterId) {
                                        $q->where('cluster_id', $clusterId);
                                    }
                                );
                            }
                        );
                    }
                );
            }
        )->whereHasMorph(
            'originalTransaction',
            '*',
            static function ($q) {
                $q->where('status', 1);
            }
        )
            ->whereBetween('created_at', $range)
            ->sum('amount');
    }

    public function totalMiniGridTransactions($miniGridId, $range)
    {
        return
            \App\Models\Transaction\Transaction::query()->whereHas(
                'meter',
                function ($q) use ($miniGridId) {
                    $q->whereHas(
                        'meterParameter',
                        function ($q) use ($miniGridId) {
                            $q->whereHas(
                                'address',
                                function ($q) use ($miniGridId) {
                                    $q->whereHas(
                                        'city',
                                        function ($q) use ($miniGridId) {
                                            $q->where('mini_grid_id', $miniGridId);
                                        }
                                    );
                                }
                            );
                        }
                    );
                }
            )->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
                ->whereBetween('created_at', $range)
                ->sum('amount');
    }

    public function totalCityTransactions($cityId, $range)
    {
        return
            \App\Models\Transaction\Transaction::query()->whereHas(
                'meter',
                function ($q) use ($cityId) {
                    $q->whereHas(
                        'meterParameter',
                        function ($q) use ($cityId) {
                            $q->whereHas(
                                'address',
                                function ($q) use ($cityId) {
                                    $q->where('city_id', $cityId);
                                }
                            );
                        }
                    );
                }
            )->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
                ->whereBetween('created_at', $range)
                ->sum('amount');
    }
}
