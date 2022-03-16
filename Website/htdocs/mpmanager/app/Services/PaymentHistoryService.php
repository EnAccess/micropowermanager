<?php

namespace App\Services;

use App\Models\PaymentHistory;
use App\Models\Person\Person;
use Carbon\CarbonImmutable;
use Faker\Provider\Payment;
use Illuminate\Database\Eloquent\Builder;

class PaymentHistoryService
{
    private PaymentHistory $paymentHistory;

    public function __construct(PaymentHistory $paymentHistory)
    {
        $this->paymentHistory = $paymentHistory;
    }

    public function findPayingCustomersInRange(
        array $customerIds,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate
    ) {
        return $this->paymentHistory->findCustomersPaidInRange($customerIds, $startDate, $endDate);
    }

    public function findCustomerLastPayment(int $customerId): PaymentHistory
    {
        return $this->paymentHistory
            ->whereHasMorph('owner', [Person::class], fn(Builder $q) => $q->where('id', $customerId))
            ->latest('created_at')
            ->first();
    }
}
