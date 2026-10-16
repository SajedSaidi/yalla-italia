<?php

namespace App\Policies;

use App\Models\User;

/**
 * The Users page is admin only. Managers manage student accounts from the
 * Students page instead, where the account and the student profile are edited together.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins can delete any account but their own; managers can delete
     * student accounts only (never admins or other managers).
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isManager() && $model->isStudent();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isManagerOrAdmin();
    }

    public function restore(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }
}
