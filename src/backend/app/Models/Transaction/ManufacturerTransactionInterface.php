<?php

namespace App\Models\Transaction;

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
 * by the manufacturer’s systems.
 * These are should be exposed through custom methods or attributes on the implementing model.
 */
interface ManufacturerTransactionInterface {
    public function agentTransaction();

    public function thirdPartyTransaction();

    public function swiftaTransaction();

    public function mesombTransaction();

    public function waveMoneyTransaction();
}
