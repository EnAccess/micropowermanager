<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use Carbon\Carbon;
use Carbon\CarbonInterval;

/**
 * @group   Payment-History
 * Class PaymentHistoryController
 */
class PaymentHistoryController {
    /**
     * @var PaymentHistory
     */
    private $history;

    /**
     * PaymentHistoryController constructor.
     *
     * @param PaymentHistory $history
     */
    public function __construct(PaymentHistory $history) {
        $this->history = $history;
    }

    /**
     * Detail.
     *
     * @urlParam payerId integer required
     * @urlParam period string required
     * @urlParam limit integer
     * @urlParam order string
     *
     * @param int    $payerId
     * @param string $period
     * @param null   $limit
     * @param string $order
     *
     * @return array<string, array<string, float|int>>
     */
    public function show(int $payerId, string $period, $limit = null, $order = 'ASC'): array {
        $period = strtoupper($period);
        switch ($period) {
            case 'D':
                $period = 'Day(created_at), Month(created_at), Year(created_at)';
                break;
            case 'W':
                $period = 'Week(created_at), Year(created_at)';
                break;
            case 'M':
                $period = 'Month(created_at), Year(created_at)';
                break;
            default:
                $period = 'Year(created_at)';
                break;
        }
        $payments = app()->make(PaymentHistory::class)->getFlow(
            'person',
            $payerId,
            $period,
            $limit,
            $order
        );

        return $this->preparePaymentFlow($payments);
    }

    /**
     * Payment Periods.
     *
     * @urlParam personId integer required
     *
     * @param $personId
     *
     * @return ApiResource
     *
     * @throws \Exception
     */
    public function getPaymentPeriod(Person $person) {
        $payments = $person->payments()->latest()->take(10)->get();

        $difference = 'no data available';
        $lastTransactionDate = null;
        if (\count($payments)) {
            $lastTransactionDate = $newest = $payments[0]->created_at;
            $newest = new Carbon($newest);
            $lastTransactionDate = $newest->diffInDays(Carbon::now()).' days ago';
            $eldest = new Carbon($payments[\count($payments) - 1]->created_at);
            $difference = $eldest->diffInDays($newest).' days';
        }

        return ApiResource::make(['difference' => $difference, 'lastTransaction' => $lastTransactionDate]);
    }

    /**
     * Person payment flow per year.
     *
     * @urlParam personId integer required
     *
     * @param int      $personId
     * @param int|null $year
     *
     * @return array<int, float>
     */
    public function byYear(int $personId, ?int $year = null): array {
        $year = $year ?? (int) date('Y');
        $payments = $this->history->getPaymentFlow('person', $personId, $year);
        $paymentFlow = array_fill(0, 11, 0);
        foreach ($payments as $payment) {
            $paymentFlow[$payment['month'] - 1] = (float) $payment['amount'];
        }

        return $paymentFlow;
    }

    /**
     * Person Debts.
     *
     * @urlParam personId integer required
     * checks if the person has any debts to the system
     *
     * @param int $personId
     *
     * @return ApiResource
     */
    public function debts($personId) {
        $accessRateDebt = 0;
        $meters = Device::query()->with('device')
            ->whereHasMorph(
                'device',
                Meter::class
            )
            ->where('person_id', $personId)
            ->get()->pluck('device');

        foreach ($meters as $meter) {
            if ($accessRateDebt += $meter->accessRatePayment) {
                $accessRateDebt += $meter->accessRatePayment->debt;
            }
        }
        $deferredDebt = 0;

        return ApiResource::make(['access_rate' => $accessRateDebt, 'deferred' => $deferredDebt]);
    }

    /**
     * Payments list with date range.
     *
     * @bodyParam begin string
     * @bodyParam end string
     *
     * @return ApiResource
     *
     * @throws \Exception
     */
    public function getPaymentRange(): ApiResource {
        $begin = request('begin'); // Y-m-d
        $end = request('end'); // Y-m- d
        // create a sequence of dates
        $period = new \DatePeriod(
            Carbon::parse($begin),
            CarbonInterval::day(),
            Carbon::parse($end.' 00:01')
        );
        $result = [];
        foreach ($period as $p) {
            $result[(new Carbon($p))->toDateString()] = ['date' => (new Carbon($p))->toDateString(), 'amount' => 0];
        }
        $payments = $this->history->getOverview($begin, $end);
        foreach ($payments as $p) {
            $result[$p['dato']] = ['date' => $p['dato'], 'amount' => $p['total']];
        }

        return ApiResource::make(array_values($result));
    }

    /**
     * @param array<int, array{aperiod: string, payment_type: string, amount: float|int}> $payments
     *
     * @return array<string, array<string, float|int>>
     */
    public function preparePaymentFlow(array $payments): array {
        $flowList = [];
        foreach ($payments as $payment) {
            $flowList[$payment['aperiod']][$payment['payment_type']] = $payment['amount'];
        }

        return $flowList;
    }
}
