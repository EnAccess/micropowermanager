<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Plugins\SteamaMeter\Models\SteamaSmsNotifiedCustomer;
use Illuminate\Database\Eloquent\Collection;

class SteamaSmsNotifiedCustomerService {
    public function __construct(
        private SteamaSmsNotifiedCustomer $steamaSmsNotifiedCustomer,
    ) {}

    /**
     * @return Collection<int, SteamaSmsNotifiedCustomer>
     */
    public function getSteamaSmsNotifiedCustomers(): Collection {
        return $this->steamaSmsNotifiedCustomer->newQuery()->get();
    }

    public function createTransactionSmsNotify(int $customerId, int $transactionId): SteamaSmsNotifiedCustomer {
        return $this->steamaSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'transaction',
            'notify_id' => $transactionId,
        ]);
    }

    public function createLowBalanceSmsNotify(int $customerId): SteamaSmsNotifiedCustomer {
        return $this->steamaSmsNotifiedCustomer->newQuery()->create([
            'customer_id' => $customerId,
            'notify_type' => 'low_balance',
        ]);
    }

    /**
     * @param array<string, mixed> $customer
     */
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
