<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
    /** @return MorphOne<Model, Model> */
    public function agentTransaction(): MorphOne;

    /** @return MorphOne<Model, Model> */
    public function thirdPartyTransaction(): MorphOne;

    /** @return MorphOne<Model, Model> */
    public function swiftaTransaction(): MorphOne;

    /** @return MorphOne<Model, Model> */
    public function mesombTransaction(): MorphOne;

    /** @return MorphOne<Model, Model> */
    public function waveMoneyTransaction(): MorphOne;
}
