<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AirtelPaymentProviderApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        // Expected: [0=api, 1=airtel-payment-provider, 2=callback, 3=companyId, 4=step]
        $companyId = $request->segment(4);

        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['callback' => 'Invalid company ID in Airtel callback URL']);
        }

        return (int) $companyId;
    }
}
