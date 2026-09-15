<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update');
    }

    public function deactivate(User $user, User $target): bool
    {
        if (! $user->can('users.deactivate') || $user->id === $target->id) {
            return false;
        }

        return ! ($target->hasRole('Super Admin') && User::role('Super Admin')->where('is_active', true)->count() <= 1);
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->can('users.reset-password');
    }

    public function manageRole(User $user, User $target): bool
    {
        return $user->can('users.manage-role');
    }
}
