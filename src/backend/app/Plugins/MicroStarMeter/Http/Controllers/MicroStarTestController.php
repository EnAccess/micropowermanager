<?php

namespace App\Plugins\MicroStarMeter\Http\Controllers;

use App\Plugins\MicroStarMeter\Modules\Api\ApiRequests;
use Illuminate\Routing\Controller;

class MicroStarTestController extends Controller {
    public function __construct(private ApiRequests $apiRequests) {}

    /**
     * @return array<string, mixed>
     */
    public function show(): array {
        return $this->apiRequests->testGet();
    }
}
