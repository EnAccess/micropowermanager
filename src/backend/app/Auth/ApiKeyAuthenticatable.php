<?php

namespace App\Auth;

use App\Models\Company;
use Illuminate\Contracts\Auth\Authenticatable;

class ApiKeyAuthenticatable implements Authenticatable {
    public function __construct(
        public private(set) Company $company,
    ) {}

    public function getAuthIdentifierName(): string {
        return 'id';
    }

    public function getAuthIdentifier(): mixed {
        return $this->company->id;
    }

    public function getAuthPassword(): string {
        return '';
    }

    public function getAuthPasswordName(): string {
        return '';
    }

    public function getRememberToken(): ?string {
        return null;
    }

    public function setRememberToken($value): void {
        // Not applicable for API key authentication
    }

    public function getRememberTokenName(): string {
        return '';
    }
}
