<?php

namespace App\Services;

use App\Models\MainSettings;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @phpstan-type MainSettingsData array{
 *     name?: string,
 *     value?: string,
 *     description?: string,
 *     is_active?: bool,
 *     created_at?: string,
 *     updated_at?: string
 * }
 *
 * @implements IBaseService<MainSettings>
 */
// FIXME: Should this not be a ISettingsService?
class MainSettingsService implements IBaseService {
    public function __construct(
        private MainSettings $mainSettings,
    ) {}

    public function getById(int $id): MainSettings {
        return $this->mainSettings->newQuery()->findOrFail($id);
    }

    /**
     * @param MainSettingsData $data
     */
    public function create(array $data): MainSettings {
        return $this->mainSettings->newQuery()->create($data);
    }

    public function update($mainSettings, $mainSettingsData): MainSettings {
        $mainSettings->update($mainSettingsData);
        $mainSettings->fresh();

        return $mainSettings;
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, MainSettings>|LengthAwarePaginator<int, MainSettings>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        return $this->mainSettings->newQuery()->get();
    }
}
