<?php

declare(strict_types=1);

namespace MPM\Transaction;

interface FullySupportedTransactionInterface {
    public static function getTransactionName(): string;
}
