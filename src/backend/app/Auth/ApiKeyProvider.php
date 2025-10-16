<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\ApiKey;
use App\Models\Company;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyProvider implements UserProvider {
    public function __construct(
        private Request $request,
    ) {}

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById(mixed $identifier): ?Authenticatable {
        $company = Company::find($identifier);

        if ($company === null) {
            return null;
        }

        return new ApiKeyAuthenticatable($company);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token) {
        // Not used for API key authentication
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(Authenticatable $user, $token): void {
        // Not used for API key authentication
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array<string, mixed> $credentials
     *
     * @return ApiKeyAuthenticatable|null
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable {
        $token = $this->extractBearerToken();

        if ($token === null) {
            return null;
        }

        $hash = hash('sha256', $token);
        $apiKey = ApiKey::query()->active()->where('token_hash', $hash)->first();

        if ($apiKey === null) {
            return null;
        }

        // Update last used timestamp
        $apiKey->forceFill(['last_used_at' => now()])->save();

        // Return the company wrapped in an Authenticatable object
        return new ApiKeyAuthenticatable($apiKey->company);
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param ApiKeyAuthenticatable $user
     * @param array<string, mixed>  $credentials
     *
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool {
        // For API key authentication, if we got here, the credentials are valid
        return true;
    }

    /**
     * Rehash the user's password if required.
     *
     * @param ApiKeyAuthenticatable $user
     * @param array<string, mixed>  $credentials
     * @param bool                  $force
     *
     * @return void
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void {
        // Not applicable for API key authentication
    }

    private function extractBearerToken(): ?string {
        $header = $this->request->header('Authorization');

        if (!$header) {
            return null;
        }

        if (Str::startsWith(Str::lower($header), 'bearer ')) {
            return trim(substr($header, 7));
        }

        return null;
    }
}
