<?php

namespace App\Services;

use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Services\Interfaces\IAssociative;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements IAssociative<PaymentHistory>
 */
class PaymentHistoryService implements IAssociative {
    public function __construct(
        private PaymentHistory $paymentHistory,
    ) {}

    public function findPayingCustomersInRange(
        array $customerIds,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ) {
        return $this->paymentHistory->findCustomersPaidInRange($customerIds, $startDate, $endDate);
    }

    public function findCustomerLastPayment(int $customerId): PaymentHistory {
        return $this->paymentHistory
            ->whereHasMorph('owner', [Person::class], fn (Builder $q) => $q->where('id', $customerId))
            ->latest('created_at')
            ->first();
    }

    /**
     * @return LengthAwarePaginator<PaymentHistory>
     */
    public function getBySerialNumber(string $serialNumber, int $paginate): LengthAwarePaginator {
        return $this->paymentHistory->newQuery()->with(['transaction', 'paidFor'])
            ->whereHas(
                'transaction',
                function ($q) use ($serialNumber) {
                    $q->where('message', $serialNumber);
                }
            )->latest()->paginate($paginate);
    }

    /**
     * @param array<string, mixed> $paymentHistoryData
     */
    public function make(array $paymentHistoryData): PaymentHistory {
        return $this->paymentHistory->newQuery()->make($paymentHistoryData);
    }

    public function save($paymentHistory): bool {
        return $paymentHistory->save();
    }
}
