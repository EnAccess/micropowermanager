<?php

namespace Inensus\SwiftaPaymentProvider\Services;

use Inensus\SwiftaPaymentProvider\Models\SwiftaAuthentication;

class SwiftaAuthenticationService {
    public function __construct(
        private SwiftaAuthentication $swiftaAuthentication,
    ) {}

    public function getSwiftaAuthentication(): SwiftaAuthentication {
        return $this->swiftaAuthentication->firstOrFail();
    }
}
