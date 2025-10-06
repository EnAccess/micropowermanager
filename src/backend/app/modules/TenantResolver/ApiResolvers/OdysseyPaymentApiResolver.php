<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OdysseyPaymentApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        $token = $this->extractBearerToken($request);
        if ($token === null) {
            throw ValidationException::withMessages(['authorization' => 'Bearer token is required']);
        }

        $hash = hash('sha256', $token);
        $apiKey = ApiKey::query()->active()->where('token_hash', $hash)->first();
        if ($apiKey === null) {
            throw ValidationException::withMessages(['authorization' => 'Invalid API token']);
        }

        $apiKey->forceFill(['last_used_at' => now()])->save();

        return (int) $apiKey->company_id;
    }

    private function extractBearerToken(Request $request): ?string {
        $header = $request->header('Authorization');
        if (!$header) {
            return null;
        }
        if (Str::startsWith(Str::lower($header), 'bearer ')) {
            return trim(substr($header, 7));
        }

        return null;
    }
}
