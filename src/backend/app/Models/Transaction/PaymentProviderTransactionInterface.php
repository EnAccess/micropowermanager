<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
 * @template TModel of Model
 */
interface PaymentProviderTransactionInterface {
    /**
     * @return MorphOne<Transaction, TModel>
     */
    public function transaction();

    /**
     * @return ManufacturerTransactionInterface
     */
    public function manufacturerTransaction();

    /**
     * @return Builder<TModel>
     */
    public function newQuery();

    /**
     * @param array<string, mixed> $options
     * @return bool
     */
    public function save(array $options = []);

    /**
     * @return string
     */
    public static function getTransactionName(): string;
}
