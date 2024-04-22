<?php

namespace Inensus\AirtelPaymentProvider\Services;

use Inensus\AirtelPaymentProvider\Models\AirtelAuthentication;

class AirtelAuthenticationService
{
    public function __construct(private AirtelAuthentication $airtelAuthentication)
    {
    }

    public function getAirtelAuthentication()
    {
        return $this->airtelAuthentication->firstOrFail();
    }
}