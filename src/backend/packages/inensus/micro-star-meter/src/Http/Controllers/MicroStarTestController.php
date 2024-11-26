<?php

namespace Inensus\MicroStarMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\MicroStarMeter\Modules\Api\ApiRequests;

class MicroStarTestController extends Controller {
    public function __construct(private ApiRequests $apiRequests) {}

    public function show() {
        return $this->apiRequests->testGet();
    }
}
