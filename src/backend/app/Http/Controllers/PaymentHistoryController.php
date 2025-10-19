<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Date;

/**
 * @group   Payment-History
 * Class PaymentHistoryController
 */
class PaymentHistoryController {
    /**
     * PaymentHistoryController constructor.
     */
    public function __construct(private PaymentHistory $history) {}

    /**
     * Detail.
     *
     * @urlParam payerId integer required
     * @urlParam period string required
     * @urlParam limit integer
     * @urlParam order string
     *
     * @return array<string, array<string, mixed>>
     */
    public function show(int $payerId, string $period, ?int $limit = null, string $order = 'ASC'): array {
        $period = strtoupper($period);
        $period = match ($period) {
            'D' => 'Day(created_at), Month(created_at), Year(created_at)',
            'W' => 'Week(created_at), Year(created_at)',
            'M' => 'Month(created_at), Year(created_at)',
            default => 'Year(created_at)',
        };
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
     * @throws \Exception
     */
    public function getPaymentPeriod(Person $person): ApiResource {
        $payments = $person->payments()->latest()->take(10)->get();

        $difference = 'no data available';
        $lastTransactionDate = null;
        if (\count($payments) > 0) {
            $lastTransactionDate = $newest = $payments[0]->created_at;
            $newest = new Carbon($newest);
            $lastTransactionDate = (int) $newest->diffInDays(Date::now()).' days ago';
            $eldest = new Carbon($payments[\count($payments) - 1]->created_at);
            $difference = (int) $eldest->diffInDays($newest).' days';
        }

        return ApiResource::make(['difference' => $difference, 'lastTransaction' => $lastTransactionDate]);
    }

    /**
     * Person payment flow per year.
     *
     * @urlParam personId integer required
     *
     * @return array<int, float>
     */
    public function byYear(int $personId, ?int $year = null): array {
        $year ??= (int) date('Y');
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
     */
    public function debts(int $personId): ApiResource {
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
     * @throws \Exception
     */
    public function getPaymentRange(): ApiResource {
        $begin = request('begin'); // Y-m-d
        $end = request('end'); // Y-m- d
        // create a sequence of dates
        $period = new \DatePeriod(
            Date::parse($begin),
            CarbonInterval::day(),
            Date::parse($end.' 00:01')
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
     * @param array<mixed> $payments
     *
     * @return array<string, array<string, mixed>>
     */
    public function preparePaymentFlow(array $payments): array {
        $flowList = [];
        foreach ($payments as $payment) {
            $flowList[$payment['aperiod']][$payment['payment_type']] = $payment['amount'];
        }

        return $flowList;
    }
}
