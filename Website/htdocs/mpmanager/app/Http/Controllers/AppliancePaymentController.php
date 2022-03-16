<?php

namespace App\Http\Controllers;

use App\Models\AssetPerson;
use App\Services\AppliancePaymentService;
use Illuminate\Http\Request;

class AppliancePaymentController extends Controller
{

    private $appliancePaymentService;

    public function __construct(AppliancePaymentService $appliancePaymentService)
    {
        $this->appliancePaymentService = $appliancePaymentService;
    }

    public function store(AssetPerson $appliancePerson, Request $request)
    {
        $this->appliancePaymentService->getPaymentForAppliance($request, $appliancePerson);
    }
}
