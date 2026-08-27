<?php

namespace App\Services;

use App\Exceptions\EntityHasChildrenException;
use App\Models\Address\Address;
use App\Models\City;
use App\Models\GeographicalInformation;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @implements IBaseService<City>
 */
class CityService implements IBaseService {
    /** @use HasCrudOperations<City> */
    use HasCrudOperations;

    public function __construct(
        private City $city,
    ) {}

    protected function crudModel(): City {
        return $this->city;
    }

    /**
     * @return array<int, int>
     */
    public function getCityIdsByMiniGridId(int $miniGridId): array {
        return $this->city->newQuery()->select('id')->where('mini_grid_id', $miniGridId)->get()->pluck('id')->toArray();
    }

    /**
     * @param string|array<string> $relation
     */
    public function getByIdWithRelation(int $cityId, string|array $relation): ?City {
        return $this->city->newQuery()->with($relation)->find($cityId);
    }

    /**
     * @param array<string, mixed> $cityData
     */
    public function create(array $cityData): City {
        $city = $this->city->newQuery()->create([
            'name' => $cityData['name'],
            'mini_grid_id' => $cityData['mini_grid_id'],
            'country_id' => $cityData['country_id'],
        ]);
        $city->location()->create(['geo_json' => GeographicalInformation::pointFromInputGeoJson($cityData['geo_json'])]);

        return $city;
    }

    /**
     * @param City                 $model
     * @param array<string, mixed> $cityData
     */
    public function update(Model $model, array $cityData): Model {
        $model->update([
            'name' => $cityData['name'] ?? $model->name,
            'mini_grid_id' => $cityData['mini_grid_id'] ?? $model->mini_grid_id,
            'country_id' => $cityData['country_id'] ?? $model->country_id,
        ]);

        if (isset($cityData['geo_json'])) {
            $model->location()->updateOrCreate([], ['geo_json' => GeographicalInformation::pointFromInputGeoJson($cityData['geo_json'])]);
        }

        return $model->load('location');
    }

    /**
     * @return Collection<int, City>|LengthAwarePaginator<int, City>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->city->newQuery()->with(['location', 'miniGrid.cluster', 'country']);

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    /**
     * The addresses that keep a village undeletable. Customer lists match a customer's
     * primary address only, so a non-primary address is invisible everywhere else.
     *
     * @return Collection<int, Address>|LengthAwarePaginator<int, Address>
     */
    public function getLinkedAddresses(City $city, ?int $limit = null): Collection|LengthAwarePaginator {
        $query = $city->addresses()
            ->with('owner')
            ->whereNotIn('id', $this->findUnreachableAddressIds($city))
            ->orderByDesc('is_primary')
            ->orderBy('id');

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    /**
     * Counts the addresses linked to a village, grouped by the kind of record that owns them.
     *
     * @return array<int, array{owner_type: string, label: string, total: int, previous: int}>
     */
    public function getLinkedAddressSummary(City $city): array {
        $counts = $city->addresses()
            ->selectRaw('owner_type, COUNT(*) as total, SUM(is_primary = 0) as previous')
            ->groupBy('owner_type')
            ->orderByDesc('total')
            ->get();

        return $counts->map(fn (Address $address): array => [
            'owner_type' => (string) $address->owner_type,
            'label' => $this->ownerLabel((string) $address->owner_type),
            'total' => (int) $address->getAttribute('total'),
            'previous' => (int) $address->getAttribute('previous'),
        ])->all();
    }

    /**
     * @return int the number of addresses that were moved
     *
     * @throws ValidationException when source and target are the same village
     */
    public function moveAddressesTo(City $source, City $target): int {
        if ($source->id === $target->id) {
            throw ValidationException::withMessages(['reassign_addresses_to' => ['Addresses cannot be moved to the village they already belong to.']]);
        }

        $this->discardUnreachableAddresses($source);

        return $source->addresses()->update(['city_id' => $target->id]);
    }

    /**
     * @param City $model
     *
     * @throws EntityHasChildrenException when the city still has addresses
     */
    public function delete(Model $model): ?bool {
        $this->discardUnreachableAddresses($model);

        $summary = $this->getLinkedAddressSummary($model);

        if ($summary !== []) {
            throw new EntityHasChildrenException($this->describeDeleteBlockers($summary));
        }

        return $model->delete();
    }

    private function discardUnreachableAddresses(City $city): void {
        $unreachableAddressIds = $this->findUnreachableAddressIds($city);

        if ($unreachableAddressIds === []) {
            return;
        }

        $city->addresses()->whereIn('id', $unreachableAddressIds)->delete();
    }

    /**
     * Addresses no one can reach: the morph type is unknown, or the record they point at
     * is deleted. PersonObserver discards a person's addresses when the person is deleted,
     * so an address surviving its owner is residue that must not hold a village hostage.
     *
     * @return array<int, int>
     */
    private function findUnreachableAddressIds(City $city): array {
        $unreachableIds = [];

        foreach ($city->addresses()->select('id', 'owner_type', 'owner_id')->get()->groupBy('owner_type') as $ownerType => $addresses) {
            $ownerClass = Relation::getMorphedModel((string) $ownerType) ?? (class_exists((string) $ownerType) ? (string) $ownerType : null);

            if ($ownerClass === null) {
                $unreachableIds = [...$unreachableIds, ...$addresses->pluck('id')->all()];
                continue;
            }

            /** @var Model $owner */
            $owner = new $ownerClass();
            // newQuery() applies the owner's global scopes, so a soft-deleted owner counts
            // as gone.
            $reachableOwnerIds = $owner->newQuery()
                ->whereIn($owner->getKeyName(), $addresses->pluck('owner_id')->all())
                ->pluck($owner->getKeyName());

            $unreachable = $addresses->whereNotIn('owner_id', $reachableOwnerIds);
            $unreachableIds = [...$unreachableIds, ...$unreachable->pluck('id')->all()];
        }

        return $unreachableIds;
    }

    /**
     * @param array<int, array{owner_type: string, label: string, total: int, previous: int}> $summary
     */
    private function describeDeleteBlockers(array $summary): string {
        $total = array_sum(array_column($summary, 'total'));

        $parts = array_map(static function (array $entry): string {
            $part = $entry['total'].' '.Str::plural($entry['label'], $entry['total']);

            if ($entry['previous'] > 0) {
                $part .= sprintf(', %d of them through a former address', $entry['previous']);
            }

            return $part;
        }, $summary);

        return sprintf(
            'Village cannot be deleted: %d %s still %s to it — %s. Move these addresses to another village or delete those records first.',
            $total,
            Str::plural('address', $total),
            $total === 1 ? 'points' : 'point',
            implode('; ', $parts),
        );
    }

    private function ownerLabel(string $ownerType): string {
        return match ($ownerType) {
            'person' => 'customer',
            default => str_replace(['-', '_'], ' ', $ownerType),
        };
    }
}
