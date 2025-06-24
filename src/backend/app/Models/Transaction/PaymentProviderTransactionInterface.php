<?php

namespace App\Models\Transaction;

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
     * Get the general-purpose system transaction associated with this provider transaction.
     *
     * This relationship is polymorphic and links the provider-specific transaction
     * to the core `Transaction` model used by the application.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function transaction();

    /**
     * Get the manufacturer-specific transaction abstraction associated with this transaction.
     *
     * This represents how the payment transaction is used to enable or interact
     * with hardware or systems from a specific manufacturer.
     *
     * @return ManufacturerTransactionInterface
     */
    public function manufacturerTransaction();

    /**
     * Create a new Eloquent query builder instance for payment provider transactions.
     */
    public function newQuery();

    /**
     * Persist the payment provider transaction.
     *
     * @return bool indicates whether the save operation was successful
     */
    public function save(array $options = []);

    /**
     * Get the name of the transaction type (used for morph map or identification).
     *
     * @return string
     */
    public static function getTransactionName(): string;
}
