<?php

namespace App\Services;

use App\Models\RegistrationTail;
use Illuminate\Support\Facades\Log;

class RegistrationTailService implements IBaseService
{
    public function __construct(private RegistrationTail $registrationTail)
    {
    }

    public function getById($id)
    {
        return $this->registrationTail->newQuery()->find($id);
    }

    public function create($registrationTailData)
    {
        return $this->registrationTail->newQuery()->create($registrationTailData);
    }

    public function update($registrationTail, $registrationTailData)
    {
        if (array_key_exists('tail', $registrationTailData)) {
            $registrationTail->update($registrationTailData);
        } else {
            $registrationTail->update(['tail' => $registrationTailData]);
        }

        $registrationTail->fresh();

        return $registrationTail;
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        return $this->registrationTail->newQuery()->get();
    }
    public function getFirst($limit = null)
    {
        return $this->registrationTail->newQuery()->first();
    }

    /**
     * @param mixed $tail
     * @param mixed $mpmPlugin
     * @param mixed $registrationTail
     * @return mixed
     */
    public function resetTail(mixed $tail, mixed $mpmPlugin, mixed $registrationTail): mixed
    {
        array_push($tail, [
            'tag' => $mpmPlugin->tail_tag,
            'component' => isset($mpmPlugin->tail_tag) ? str_replace(
                " ",
                "-",
                $mpmPlugin->tail_tag
            ) : null,
            'adjusted' => !isset($mpmPlugin->tail_tag),
        ]);
        $this->update(
            $registrationTail,
            ['tail' => json_encode($tail)]
        );
        return $tail;
    }
}
