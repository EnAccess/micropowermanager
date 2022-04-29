<?php

namespace App\Services;

use App\Models\Restriction;

class RestrictionService extends BaseService
{
    public function __construct(private Restriction $restriction)
    {
        parent::__construct([$this->restriction]);
    }

    public function getRestrictionForTarget($target)
    {
        return $this->restriction->newQuery()->where('target', $target)->firstOrFail();
    }
}