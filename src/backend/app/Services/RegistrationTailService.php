<?php

namespace App\Services;

use App\Models\MpmPlugin;
use App\Models\RegistrationTail;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<RegistrationTail>
 */
class RegistrationTailService implements IBaseService {
    /** @use HasCrudOperations<RegistrationTail> */
    use HasCrudOperations;

    public function __construct(
        private RegistrationTail $registrationTail,
    ) {}

    protected function crudModel(): RegistrationTail {
        return $this->registrationTail;
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
