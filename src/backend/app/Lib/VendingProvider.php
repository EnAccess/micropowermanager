<?php

declare(strict_types=1);

namespace App\Lib;

final readonly class VendingProvider {
    /**
     * @param class-string<IVendingProvider> $apiClass
     * @param list<string>                   $manufacturerApiNames the `manufacturers.api_name` of every OEM whose meters this provider can vend
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $apiClass,
        public int $mpmPluginId,
        public array $manufacturerApiNames,
    ) {}
}
