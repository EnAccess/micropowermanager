<?php

namespace App\Services;

use App\Models\User;

class ClusterManagerService {
    public function __construct(private User $user) {}

    public function findManagerById(int $managerId): User {
        return $this->user->find($managerId);
    }
}
