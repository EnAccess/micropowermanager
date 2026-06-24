<?php

namespace App\Exceptions;

/**
 * Thrown when an agent transaction would push the agent past their allowed
 * risk balance, so the transaction must be refused.
 */
class AgentRiskBalanceExceeded extends MpmException {
    protected int $httpStatusCode = 403;
}
