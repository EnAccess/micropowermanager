<?php

namespace App\Events;

use App\Models\AccessRate\AccessRate;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PaymentSuccessEvent.
 *
 * Dispatch this event to asynchronously store a successful
 * payment in the corresponding payment history table..
 *
 * @property int                                      $amount         The amount of the payment.
 * @property string                                   $paymentService The name of the Payment gateway.
 * @property string                                   $paymentType    Type of payment.
 * @property string                                   $sender         Unclear.
 * @property AccessRate|ApplianceRate|Appliance|Token $paidFor        What the payment was for.
 * @property Person                                   $payer          The person related to this payment.
 * @property Transaction                              $transaction    The transaction related to this payment.
 */
class PaymentSuccessEvent {
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $amount,
        public string $paymentService,
        public string $paymentType,
        public string $sender,
        public AccessRate|ApplianceRate|Appliance|Token $paidFor,
        public Person|AppliancePerson $payer,  // Is this correct? It should only be one of the two.
        public Transaction $transaction,
    ) {}
}
