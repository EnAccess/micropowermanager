<?php

namespace App\Models\Transaction;

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
    /**
     * Get the agent transaction relationship.
     */
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function agentTransaction(): MorphOne;

    /**
     * Get the third party transaction relationship.
     */
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function thirdPartyTransaction(): MorphOne;

    /**
     * Get the Swifta transaction relationship.
     */
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function swiftaTransaction(): MorphOne;

    /**
     * Get the Mesomb transaction relationship.
     */
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function mesombTransaction(): MorphOne;

    /**
     * Get the WaveMoney transaction relationship.
     */
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function waveMoneyTransaction(): MorphOne;
}
