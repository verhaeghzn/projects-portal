<?php

namespace App\Policies;

use App\Models\User;
use STS\FilamentImpersonate\Facades\Impersonation;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     * Allow when impersonating to avoid 403 before redirect completes.
     */
    public function viewAny(User $user): bool
    {
        if (Impersonation::isImpersonating()) {
            return true;
        }

        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('Administrator');
    }
}
