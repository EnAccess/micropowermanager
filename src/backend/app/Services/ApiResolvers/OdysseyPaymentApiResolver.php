<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Auth\ApiKeyAuthenticatable;
use App\Models\Company;
use App\Models\User;
use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OdysseyPaymentApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        // The user should already be authenticated by the 'api-key' guard
        /** @var ApiKeyAuthenticatable|Company|User|null $user */
        $user = auth('api-key')->user();

        if ($user === null) {
            throw ValidationException::withMessages(['authorization' => 'Company could not be resolved from authenticated API key']);
        }

        if ($user instanceof ApiKeyAuthenticatable) {
            return $user->getCompany()->id;
        }

        return $user->id;
    }
}
