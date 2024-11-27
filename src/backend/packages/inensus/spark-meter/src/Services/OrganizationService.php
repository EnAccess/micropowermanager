<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmOrganization;

class OrganizationService {
    private $organization;

    public function __construct(
        SmOrganization $organization,
    ) {
        $this->organization = $organization;
    }

    public function getOrganizations() {
        return $this->organization->newQuery()->where('id', '>', 0)->get();
    }

    public function createOrganization($organizationData) {
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

    public function deleteOrganization() {
        $organization = $this->organization->newQuery()->first();
        $organization->delete();
    }
}
