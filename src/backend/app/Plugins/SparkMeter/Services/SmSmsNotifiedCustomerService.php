<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Models\SmSmsNotifiedCustomer;
use Illuminate\Database\Eloquent\Collection;

class SmSmsNotifiedCustomerService {
    public function __construct(
        private SmSmsNotifiedCustomer $smSmsNotifiedCustomer,
    ) {}

    /**
     * @return Collection<int, SmSmsNotifiedCustomer>
     */
    public function getSmsNotifiedCustomers(): Collection {
        return $this->smSmsNotifiedCustomer->newQuery()->get();
    }

    public function createTransactionSmsNotify(string $customerId, int $transactionId): SmSmsNotifiedCustomer {
        return $this->smSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'transaction',
            'notify_id' => $transactionId,
        ]);
    }

    public function createLowBalanceSmsNotify(string $customerId): SmSmsNotifiedCustomer {
        return $this->smSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'low_balance',
        ]);
    }

    /**
     * @param array<string, mixed> $customer
     */
    public function removeLowBalancedCustomer(array $customer): bool {
        if ($customer['low_balance_limit'] >= $customer['credit_balance']) {
            return false;
        }

        $notifiedCustomer = $this->smSmsNotifiedCustomer->newQuery()->where(
            'customer_id',
            $customer['customer_id']
        )->first();
        if ($notifiedCustomer) {
            $notifiedCustomer->delete();
        }

        return true;
    }
}
