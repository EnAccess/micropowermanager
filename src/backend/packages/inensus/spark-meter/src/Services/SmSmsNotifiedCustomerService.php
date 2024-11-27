<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmSmsNotifiedCustomer;

class SmSmsNotifiedCustomerService {
    private $smSmsNotifiedCustomer;

    public function __construct(
        SmSmsNotifiedCustomer $smSmsNotifiedCustomer,
    ) {
        $this->smSmsNotifiedCustomer = $smSmsNotifiedCustomer;
    }

    public function getSmsNotifiedCustomers() {
        return $this->smSmsNotifiedCustomer->newQuery()->get();
    }

    public function createTransactionSmsNotify($customerId, $transactionId) {
        return $this->smSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'transaction',
            'notify_id' => $transactionId,
        ]);
    }

    public function createLowBalanceSmsNotify($customerId) {
        return $this->smSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'low_balance',
        ]);
    }

    public function removeLowBalancedCustomer($customer) {
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
