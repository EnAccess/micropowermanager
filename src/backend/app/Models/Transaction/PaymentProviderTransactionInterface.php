<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Interface PaymentProviderTransactionInterface.
 *
 * Represents a transaction specific to a payment provider.
 *
 * This provider-specific transaction is:
 * - Linked to a general-purpose system `Transaction` model (polymorphic).
 * - Optionally associated with a `ManufacturerTransactionInterface` implementation,
 *   which represents how the transaction enables or interacts with manufacturer-specific devices.
 *
 * Implementations are expected to support querying and persistence.
 */
interface PaymentProviderTransactionInterface {
    /**
     * Get the base transaction relationship.
     */
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function transaction(): MorphOne;

    /**
     * Get the corresponding manufacturer transaction relationship.
     */
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function manufacturerTransaction(): MorphTo;

    /**
     * @return Builder<Model>
     */
    public function newQuery();

    /**
     * @param array<string, mixed> $options
     *
     * @return bool
     */
    public function save(array $options = []);

    /**
     * @return string
     */
    public static function getTransactionName(): string;
}
