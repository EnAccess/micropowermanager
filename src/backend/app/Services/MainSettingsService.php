<?php

namespace App\Services;

use App\Models\MainSettings;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;

/**
 * @phpstan-type MainSettingsData array{
 *     name?: string,
 *     value?: string,
 *     description?: string,
 *     protected_page_password?: string,
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
        if (isset($data['protected_page_password'])) {
            $data['protected_page_password'] = Crypt::encrypt($data['protected_page_password']);
        }

        return $this->mainSettings->newQuery()->create($data);
    }

    public function update($mainSettings, $mainSettingsData): MainSettings {
        if (isset($mainSettingsData['protected_page_password'])) {
            $mainSettingsData['protected_page_password'] = Crypt::encrypt($mainSettingsData['protected_page_password']);
        }

        $mainSettings->update($mainSettingsData);
        $mainSettings->fresh();

        return $mainSettings;
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, MainSettings>|LengthAwarePaginator<MainSettings>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        return $this->mainSettings->newQuery()->get();
    }
}
