<?php

namespace App\Services;

use App\Models\MpmPlugin;
use App\Models\RegistrationTail;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<RegistrationTail>
 */
class RegistrationTailService implements IBaseService {
    public function __construct(
        private RegistrationTail $registrationTail,
    ) {}

    public function getById(int $id): RegistrationTail {
        return $this->registrationTail->newQuery()->find($id);
    }

    /**
     * @param array<string, mixed> $registrationTailData
     */
    public function create(array $registrationTailData): RegistrationTail {
        return $this->registrationTail->newQuery()->create($registrationTailData);
    }

    /**
     * @param array<string, mixed> $registrationTailData
     */
    public function update($registrationTail, array $registrationTailData): RegistrationTail {
        if (array_key_exists('tail', $registrationTailData)) {
            $registrationTail->update($registrationTailData);
        } else {
            $registrationTail->update(['tail' => $registrationTailData]);
        }

        $registrationTail->fresh();

        return $registrationTail;
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, RegistrationTail>|LengthAwarePaginator<int, RegistrationTail>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        return $this->registrationTail->newQuery()->get();
    }

    public function getFirst(): RegistrationTail {
        return $this->registrationTail->newQuery()->firstOr(fn () => $this->registrationTail->create(['tail' => json_encode([])]));
    }

    public function addMpmPluginToRegistrationTail(RegistrationTail $registrationTail, MpmPlugin $mpmPlugin): RegistrationTail {
        $tail = empty($registrationTail->tail) ? [] : json_decode($registrationTail->tail, true);

        $tail[] = [
            'tag' => $mpmPlugin->tail_tag,
            'component' => isset($mpmPlugin->tail_tag) ? str_replace(
                ' ',
                '-',
                $mpmPlugin->tail_tag
            ) : null,
            'adjusted' => !isset($mpmPlugin->tail_tag),
        ];

        return $this->update(
            $registrationTail,
            ['tail' => json_encode($tail)]
        );
    }

    public function removeMpmPluginFromRegistrationTail(RegistrationTail $registrationTail, MpmPlugin $mpmPlugin): RegistrationTail {
        $tail = empty($registrationTail->tail) ? [] : json_decode($registrationTail->tail, true);

        $updatedTail = array_filter($tail, fn (array $item): bool => $item['tag'] !== $mpmPlugin->tail_tag);

        return $this->update(
            $registrationTail,
            ['tail' => array_values($updatedTail)]
        );
    }
}
