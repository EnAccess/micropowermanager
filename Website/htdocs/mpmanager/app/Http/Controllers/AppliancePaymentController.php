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
        try {
            $this->appliancePaymentService->getPaymentForAppliance($request, $appliancePerson);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
