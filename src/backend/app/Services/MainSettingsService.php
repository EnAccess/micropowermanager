<?php

namespace App\Services;

use App\Models\MainSettings;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MainSettings>
 */
// FIXME: Should this not be a ISettingsService?
class MainSettingsService implements IBaseService {
    public function __construct(
        private MainSettings $mainSettings,
    ) {}

    public function getById(int $id): MainSettings {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(array $data): MainSettings {
        throw new \Exception('Method create() not yet implemented.');
    }

    public function update($mainSettings, $mainSettingsData): MainSettings {
        $mainSettings->update($mainSettingsData);
        $mainSettings->fresh();

        return $mainSettings;
    }

    public function delete($model): ?bool {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        return $this->mainSettings->newQuery()->get();
    }
}
