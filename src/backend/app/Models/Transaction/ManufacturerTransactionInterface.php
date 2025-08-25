<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

/**
 * Interface ManufacturerTransactionInterface.
 *
 * Represents an abstraction over manufacturer-specific transaction implementations.
 *
 * Each method provides access to a specific payment provider transaction record that corresponds
 * to how the payment is processed by the corresponding payment provider.
 *
 *  Implementations may also expose additional metadata such as `site_ids`,
 * `external_ids`, device identifiers, or configuration parameters required
 * by the manufacturer's systems.
 * These should be exposed through custom methods or attributes on the implementing model.
 */
interface ManufacturerTransactionInterface {
    /** @return MorphOne<AgentTransaction, Model> */
    public function agentTransaction(): MorphOne;

    /** @return MorphOne<ThirdPartyTransaction, Model> */
    public function thirdPartyTransaction(): MorphOne;

    /** @return MorphOne<SwiftaTransaction, Model> */
    public function swiftaTransaction(): MorphOne;

    /** @return MorphOne<MesombTransaction, Model> */
    public function mesombTransaction(): MorphOne;

    /** @return MorphOne<WaveMoneyTransaction, Model> */
    public function waveMoneyTransaction(): MorphOne;
}
