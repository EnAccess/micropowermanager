<?php

namespace Inensus\Prospect\Services;

use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\Company;
use App\Models\DatabaseProxy;
use App\Models\User;

class ProspectAgentTransformer {
    /**
     * Transform an Agent model into a Prospect agent array.
     *
     * @return array<string, mixed>
     */
    public function transform(Agent $agent): array {
        $primaryAddress = $this->getPrimaryAddress($agent);
        $person = $agent->person;
        $companyName = $this->getCompanyName();

        $externalId = (string) $agent->id;
        $fullName = $person ? trim(($person->name ?? '').' '.($person->surname ?? '')) : null;

        $addressParts = [];
        if ($primaryAddress?->street) {
            $addressParts[] = $primaryAddress->street;
        }
        if ($primaryAddress?->city?->name) {
            $addressParts[] = $primaryAddress->city->name;
        }
        $addressString = empty($addressParts) ? null : implode(', ', $addressParts);

        $countryCode = $primaryAddress?->city?->country->country_code ?? null;
        $latitude = null;
        $longitude = null;

        if ($primaryAddress?->geo?->points) {
            $points = explode(',', $primaryAddress->geo->points);
            if (count($points) >= 2) {
                $latitude = (float) trim($points[0]);
                $longitude = (float) trim($points[1]);
            }
        }

        $email = $agent->email ?? $primaryAddress?->email;

        $phone = $primaryAddress?->phone;

        $locationArea1 = null;
        $locationArea2 = null;
        $locationArea3 = null;
        $locationArea4 = null;
        $locationArea5 = null;

        if ($primaryAddress?->city) {
            $city = $primaryAddress->city;
            if ($city->cluster) {
                $locationArea1 = $city->cluster->name ?? null;
            }
            $locationArea2 = $city->name ?? null;
        }

        $agentType = $this->determineAgentType($agent);

        return [
            'external_id' => $externalId,
            'name' => $fullName,
            'company' => $companyName,
            'address' => $addressString,
            'email' => $email,
            'agent_type' => $agentType,
            'gender' => null,
            'phone' => $phone,
            'country' => $countryCode,
            'country_autogen' => $countryCode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_area_1' => $locationArea1,
            'location_area_2' => $locationArea2,
            'location_area_3' => $locationArea3,
            'location_area_4' => $locationArea4,
            'location_area_5' => $locationArea5,
        ];
    }

    /**
     * Get the primary address for an agent.
     */
    private function getPrimaryAddress(?Agent $agent): ?Address {
        if (!$agent instanceof Agent) {
            return null;
        }

        $person = $agent->person;
        if (!$person) {
            return null;
        }

        return $person->addresses()
            ->where('is_primary', true)
            ->with(['city.country', 'city.cluster', 'geo'])
            ->first();
    }

    /**
     * Get company name from the current context.
     */
    private function getCompanyName(): ?string {
        try {
            $user = User::query()->first();
            if (!$user) {
                return null;
            }

            $databaseProxy = app(DatabaseProxy::class);
            $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();
            $company = Company::query()->find($companyId);

            return $company?->name;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Determine the agent type based on their activities.
     * If an agent has sold appliances, they are a sales_agent.
     * Otherwise, they are an installer.
     *
     * @return 'sales_agent'|'installer'
     */
    private function determineAgentType(Agent $agent): string {
        $hasSoldAppliances = $agent->soldAppliances()->count() > 0;

        return $hasSoldAppliances ? 'sales_agent' : 'installer';
    }
}
