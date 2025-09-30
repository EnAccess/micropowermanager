<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Base class for payment provider transaction models.
 *
 * This abstract class provides common relationship methods that are shared across all payment provider transaction implementations.
 */
abstract class BasePaymentProviderTransaction extends BaseModel {
    /**
     * Get the base transaction relationship.
     *
     * @return MorphOne<Transaction, $this>
     */
    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'original_transaction');
    }

    /**
     * Get the corresponding manufacturer transaction relationship.
     *
     * @return MorphTo<Model, $this>
     */
    public function manufacturerTransaction(): MorphTo {
        return $this->morphTo();
    }

    /**
     * Get a new query builder for the model.
     *
     * @return Builder<static>
     */
    public function newQuery(): Builder {
        /** @var Builder<static> $query */
        $query = parent::newQuery();

        return $query;
    }

    /**
     * Save the model to the database.
     *
     * @param array<string, mixed> $options
     *
     * @return bool
     */
    public function save(array $options = []): bool {
        return parent::save($options);
    }

    /**
     * Each payment provider transaction must expose its relation name.
     */
    abstract public static function getTransactionName(): string;
}
