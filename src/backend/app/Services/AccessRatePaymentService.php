<?php

namespace App\Services;

use App\Models\AccessRate\AccessRatePayment;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<AccessRatePayment>
 */
class AccessRatePaymentService implements IBaseService {
    /** @use HasCrudOperations<AccessRatePayment> */
    use HasCrudOperations;

    public function __construct(
        private AccessRatePayment $accessRatePayment,
    ) {}

    protected function crudModel(): AccessRatePayment {
        return $this->accessRatePayment;
    }

    public function getAccessRatePaymentByMeter(mixed $meter): ?AccessRatePayment {
        return $meter->accessRatePayment()->first();
    }
}
