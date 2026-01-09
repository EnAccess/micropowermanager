<?php

namespace Inensus\MicroStarMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\MicroStarMeter\Modules\Api\ApiRequests;

class MicroStarTestController extends Controller {
    public function __construct(private ApiRequests $apiRequests) {}

    /**
     * @return array<string, mixed>
     */
    public function show(): array {
        return $this->apiRequests->testGet();
    }
}
