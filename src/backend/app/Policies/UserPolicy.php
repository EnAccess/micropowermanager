<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy {
    public function view(User $currentUser, User $targetUser): bool {
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        return $currentUser->can('users');
    }

    public function create(User $currentUser): bool {
        return $currentUser->can('users');
    }

    public function update(User $currentUser, User $targetUser): bool {
        // Users can't update themselves to prevent privilege escalation
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Check if target user is an owner
        if ($targetUser->hasRole('owner')) {
            // Only users with 'users.manage-owner' permission can update owners
            return $currentUser->can('users.manage-owner');
        }

        // Check if target user is an admin
        if ($targetUser->hasRole('admin')) {
            // Only users with 'users.manage-admin' permission can update admins
            return $currentUser->can('users.manage-admin');
        }

        // For other roles (financial-manager, user), basic 'users' permission is enough
        return $currentUser->can('users');
    }

    public function delete(User $currentUser, User $targetUser): bool {
        // Users can't delete themselves
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Check if target user is an owner
        if ($targetUser->hasRole('owner')) {
            return $currentUser->can('users.manage-owner');
        }

        // Check if target user is an admin
        if ($targetUser->hasRole('admin')) {
            return $currentUser->can('users.manage-admin');
        }

        return $currentUser->can('users');
    }

    public function assignRole(User $currentUser, string $roleName): bool {
        // Check if trying to assign owner role
        if ($roleName === 'owner') {
            return $currentUser->can('users.manage-owner');
        }

        // Check if trying to assign admin role
        if ($roleName === 'admin') {
            return $currentUser->can('users.manage-admin');
        }

        // For other roles, basic 'users' permission is enough
        return $currentUser->can('users');
    }
}
