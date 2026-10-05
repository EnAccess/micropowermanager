<?php

declare(strict_types=1);

namespace App\Exceptions;

class OperatorDashboardTenantNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
