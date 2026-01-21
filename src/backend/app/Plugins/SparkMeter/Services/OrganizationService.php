<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Models\SmOrganization;
use Illuminate\Database\Eloquent\Collection;

class OrganizationService {
    public function __construct(
        private SmOrganization $organization,
    ) {}

    /**
     * @return Collection<int, SmOrganization>
     *
     * @throws \InvalidArgumentException
     */
    public function getOrganizations(): Collection {
        return $this->organization->newQuery()->where('id', '>', 0)->get();
    }

    /**
     * @param array<string, mixed> $organizationData
     */
    public function createOrganization(array $organizationData): SmOrganization|bool {
        $organization = $this->organization->newQuery()->first();
        if (!$organization) {
            return $this->organization->newQuery()->create([
                'organization_id' => $organizationData['id'],
                'code' => $organizationData['code'],
                'display_name' => $organizationData['display_name'],
                'name' => $organizationData['name'],
            ]);
        } else {
            return $organization->update([
                'organization_id' => $organizationData['id'],
                'code' => $organizationData['code'],
                'display_name' => $organizationData['display_name'],
                'name' => $organizationData['name'],
            ]);
        }
    }

    public function deleteOrganization(): void {
        $organization = $this->organization->newQuery()->first();
        $organization->delete();
    }
}
