<?php

namespace App\Services\ImportServices;

final readonly class UserRoleItem {
    /**
     * @param list<string>|null $permissions
     */
    public function __construct(
        public string $name,
        public ?array $permissions,
    ) {}
}
