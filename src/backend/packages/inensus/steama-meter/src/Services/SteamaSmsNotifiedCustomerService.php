<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSmsNotifiedCustomer;

class SteamaSmsNotifiedCustomerService {
    public function __construct(private SteamaSmsNotifiedCustomer $steamaSmsNotifiedCustomer) {}

    public function getSteamaSmsNotifiedCustomers() {
        return $this->steamaSmsNotifiedCustomer->newQuery()->get();
    }

    public function createTransactionSmsNotify($customerId, $transactionId) {
        return $this->steamaSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'transaction',
            'notify_id' => $transactionId,
        ]);
    }

    public function createLowBalanceSmsNotify($customerId) {
        return $this->steamaSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'low_balance',
        ]);
    }

    public function removeLowBalancedCustomer(array $customer): bool {
        if ($customer['low_balance_warning'] >= $customer['account_balance']) {
            return false;
        }

        $notifiedCustomer = $this->steamaSmsNotifiedCustomer->newQuery()->where(
            'customer_id',
            $customer['customer_id']
        )->first();
        if ($notifiedCustomer) {
            $notifiedCustomer->delete();
        }

        return true;
    }
}
