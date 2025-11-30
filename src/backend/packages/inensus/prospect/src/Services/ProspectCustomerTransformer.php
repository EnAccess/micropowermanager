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
        $additionalData = $person->additional_json ?? [];

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
        $addressString = !empty($addressParts) ? implode(', ', $addressParts) : null;

        $countryCode = $primaryAddress?->city?->country?->country_code ?? null;

        $gender = $this->mapGender($person->sex);

        $birthYear = $this->extractBirthYear($person->birth_date);

        return [
            'external_id' => $externalId,
            'profession' => $additionalData['profession'] ?? null,
            'phone_2' => $secondaryAddress?->phone ?? null,
            'phone' => $primaryAddress?->phone ?? null,
            'identification_number' => $additionalData['identification_number'] ?? null,
            'identification_type' => $additionalData['identification_type'] ?? null,
            'household_size' => isset($additionalData['household_size']) ? (int) $additionalData['household_size'] : null,
            'gender' => $gender,
            'former_electricity_source' => $additionalData['former_electricity_source'] ?? null,
            'email' => $primaryAddress?->email ?? null,
            'country' => $countryCode,
            'birth_year' => $birthYear,
            'address' => $addressString,
            'name' => $fullName ?: null
        ];
    }

    /**
     * Get the primary address for a person.
     */
    private function getPrimaryAddress(?Person $person): ?Address {
        if (!$person) {
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
        if (!$person) {
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
     * Map sex field to Prospect gender enum (M, F, O).
     */
    private function mapGender(?string $sex): ?string {
        if (!$sex) {
            return null;
        }

        $normalized = strtolower(trim($sex));

        return match ($normalized) {
            'male', 'm' => 'M',
            'female', 'f' => 'F',
            'other', 'o' => 'O',
            default => null,
        };
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
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
