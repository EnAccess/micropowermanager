<?php

namespace App\Services\ImportServices;

final readonly class CustomerImportItem {
    public function __construct(
        public string $name,
        public string $surname,
        public ?string $title,
        public ?string $birthDate,
        public ?string $gender,
        public ?string $email,
        public ?string $phone,
        public ?string $street,
        public ?string $city,
        public ?string $devices,
    ) {}
}
