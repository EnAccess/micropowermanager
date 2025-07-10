<?php

namespace App\Services;

use App\Models\Country;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<Country>
 */
class CountryService implements IBaseService {
    public function __construct(
        private Country $country,
    ) {}

    public function getByCode(?string $countryCode): Country {
        return $countryCode !== null ? $this->country->where('country_code', $countryCode)->first() : $countryCode;
    }

    public function getById(int $id): Country {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Country {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): Country {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, Country>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
