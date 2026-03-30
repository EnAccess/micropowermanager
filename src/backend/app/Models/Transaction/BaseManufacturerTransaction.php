<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use App\Plugins\MesombPaymentProvider\Models\MesombTransaction;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Base class for manufacturer transaction models.
 *
 * This abstract class provides common relationship methods that are shared across all manufacturer transaction implementations.
 */
abstract class BaseManufacturerTransaction extends BaseModel {
    /**
     * Get the agent transaction relationship.
     *
     * @return MorphOne<AgentTransaction, $this>
     */
    public function agentTransaction(): MorphOne {
        return $this->morphOne(AgentTransaction::class, 'manufacturer_transaction');
    }

    /**
     * Get the third party transaction relationship.
     *
     * @return MorphOne<ThirdPartyTransaction, $this>
     */
    public function thirdPartyTransaction(): MorphOne {
        return $this->morphOne(ThirdPartyTransaction::class, 'manufacturer_transaction');
    }

    /**
     * Get the Swifta transaction relationship.
     *
     * @return MorphOne<SwiftaTransaction, $this>
     */
    public function swiftaTransaction(): MorphOne {
        return $this->morphOne(SwiftaTransaction::class, 'manufacturer_transaction');
    }

    /**
     * Get the Mesomb transaction relationship.
     *
     * @return MorphOne<MesombTransaction, $this>
     */
    public function mesombTransaction(): MorphOne {
        return $this->morphOne(MesombTransaction::class, 'manufacturer_transaction');
    }

    /**
     * Get the WaveMoney transaction relationship.
     *
     * @return MorphOne<WaveMoneyTransaction, $this>
     */
    public function waveMoneyTransaction(): MorphOne {
        return $this->morphOne(WaveMoneyTransaction::class, 'manufacturer_transaction');
    }

    /**
     * Get the Paystack transaction relationship.
     *
     * @return MorphOne<PaystackTransaction, $this>
     */
    public function paystackTransaction(): MorphOne {
        return $this->morphOne(PaystackTransaction::class, 'manufacturer_transaction');
    }
}
