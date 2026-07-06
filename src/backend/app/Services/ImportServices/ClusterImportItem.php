<?php

namespace App\Services\ImportServices;

final readonly class ClusterImportItem {
    /**
     * @param array<string, mixed>|null $geoJson
     */
    public function __construct(
        public string $clusterName,
        public ?string $manager,
        public ?array $geoJson,
        public ?string $miniGrids,
        public ?string $villages,
    ) {}
}
