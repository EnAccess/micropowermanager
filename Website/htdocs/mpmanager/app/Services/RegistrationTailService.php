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
        $registrationTail->update($registrationTailData);
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

}