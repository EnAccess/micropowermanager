<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;

class ApiKeyGuard implements Guard {
    use GuardHelpers;

    /**
     * Indicates if the user was authenticated via a recaller cookie.
     *
     * @var bool
     */
    protected $loggedOut = false;

    public function __construct(
        UserProvider $provider,
    ) {
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?Authenticatable {
        if (!is_null($this->user)) {
            return $this->user;
        }

        return $this->user = $this->provider->retrieveByCredentials([]);
    }

    /**
     * Validate a user's credentials.
     *
     * @param array<string, mixed> $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = []): bool {
        return !is_null($this->provider->retrieveByCredentials($credentials));
    }

    /**
     * Set the current user.
     */
    public function setUser(?Authenticatable $user): static {
        $this->user = $user;
        $this->loggedOut = false;

        return $this;
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): ?int {
        $user = $this->user();

        return $user?->id;
    }

    /**
     * Get the company ID for the currently authenticated user.
     * This is a convenience method for API key authentication.
     */
    public function getCompanyId(): ?int {
        /** @var ApiKeyAuthenticatable|null $user */
        $user = $this->user();

        return $user?->getCompany()?->id;
    }
}
