<?php

namespace App\Policies;

use App\Models\Transaction\Transaction;
use App\Models\User;

class TransactionPolicy {
    public function view(User $user, Transaction $transaction): bool {
        return $user->can('payments.view');
    }

    public function refund(User $user, Transaction $transaction): bool {
        return $user->can('payments.refund');
    }
}
