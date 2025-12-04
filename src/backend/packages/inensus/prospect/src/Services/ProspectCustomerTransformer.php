<?php

namespace Inensus\Prospect\Services;

use App\Models\Address\Address;
use App\Models\Person\Person;
use Illuminate\Support\Carbon;

class ProspectCustomerTransformer {
    /**
     * Transform a Person model into a Prospect customer array.
     *
     * @return array<string, mixed>
     */
    public function transform(Person $person): array {
        $primaryAddress = $this->getPrimaryAddress($person);
        $secondaryAddress = $this->getSecondaryAddress($person);

        $externalId = (string) $person->id;
        $fullName = trim(($person->name ?? '').' '.($person->surname ?? ''));

        // Build address string
        $addressParts = [];
        if ($primaryAddress?->street) {
            $addressParts[] = $primaryAddress->street;
        }
        if ($primaryAddress?->city?->name) {
            $addressParts[] = $primaryAddress->city->name;
        }
        $addressString = empty($addressParts) ? null : implode(', ', $addressParts);

        $countryCode = $primaryAddress?->city?->country->country_code ?? null;

        $birthYear = $this->extractBirthYear($person->birth_date);

        return [
            'external_id' => $externalId,
            'profession' => $person->education,
            'phone_2' => $secondaryAddress?->phone,
            'phone' => $primaryAddress?->phone,
            'identification_number' => $person->personDocument?->id,
            'identification_type' => $person->personDocument?->type,
            'household_size' => null,
            'gender' => null,
            'former_electricity_source' => null,
            'email' => $primaryAddress?->email,
            'country' => $countryCode,
            'birth_year' => $birthYear,
            'address' => $addressString,
            'name' => $fullName ?: null,
        ];
    }

    /**
     * Get the primary address for a person.
     */
    private function getPrimaryAddress(?Person $person): ?Address {
        if (!$person instanceof Person) {
            return null;
        }

        return $person->addresses()
            ->where('is_primary', true)
            ->with(['city.country', 'geo'])
            ->first();
    }

    /**
     * Get a secondary address for a person (for phone_2).
     */
    private function getSecondaryAddress(?Person $person): ?Address {
        if (!$person instanceof Person) {
            return null;
        }

        return $person->addresses()
            ->where('is_primary', false)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->with(['city.country', 'geo'])
            ->first();
    }

    /**
     * Extract birth year from birth_date (handles both Carbon instances and strings).
     */
    private function extractBirthYear(mixed $birthDate): ?int {
        if (!$birthDate) {
            return null;
        }

        if ($birthDate instanceof Carbon) {
            return (int) $birthDate->year;
        }

        if (is_string($birthDate)) {
            try {
                $parsed = Carbon::parse($birthDate);

                return (int) $parsed->year;
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
