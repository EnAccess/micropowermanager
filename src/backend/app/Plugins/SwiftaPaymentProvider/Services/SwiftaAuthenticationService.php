<?php

namespace App\Plugins\SwiftaPaymentProvider\Services;

use App\Plugins\SwiftaPaymentProvider\Models\SwiftaAuthentication;

class SwiftaAuthenticationService {
    public function __construct(
        private SwiftaAuthentication $swiftaAuthentication,
    ) {}

    public function getSwiftaAuthentication(): SwiftaAuthentication {
        return $this->swiftaAuthentication->firstOrFail();
    }
}
