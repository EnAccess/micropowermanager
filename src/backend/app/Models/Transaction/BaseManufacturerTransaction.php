<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

/**
 * Base class for manufacturer transaction models.
 *
 * This abstract class provides common relationship methods that are shared
 * across all manufacturer transaction implementations, eliminating code duplication
 * that was previously handled through interfaces.
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
}
