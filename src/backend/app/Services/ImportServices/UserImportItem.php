<?php

namespace App\Services\ImportServices;

final readonly class UserImportItem {
    /**
     * @param list<UserRoleItem> $roles
     * @param list<string>       $allPermissions
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public ?int $companyId,
        public array $roles,
        public array $allPermissions,
    ) {}
}
