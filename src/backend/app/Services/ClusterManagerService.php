<?php

namespace App\Services;

use App\Models\User;

class ClusterManagerService {
    private User $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function findManagerById(int $managerId): User {
        return $this->user->find($managerId);
    }
}
