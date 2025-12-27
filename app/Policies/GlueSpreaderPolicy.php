<?php

namespace App\Policies;

use App\Models\GlueSpreader;
use App\Models\User;

class GlueSpreaderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessGlueSpreaders();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GlueSpreader $glueSpreader): bool
    {
        return $user->canAccessGlueSpreaders();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccessGlueSpreaders();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GlueSpreader $glueSpreader): bool
    {
        return $user->canAccessGlueSpreaders();
    }

    /**
     * Determine whether the user can soft delete the model.
     * Any user with glue spreader access can soft delete.
     */
    public function delete(User $user, GlueSpreader $glueSpreader): bool
    {
        return $user->canAccessGlueSpreaders();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GlueSpreader $glueSpreader): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only Super can hard delete.
     */
    public function forceDelete(User $user, GlueSpreader $glueSpreader): bool
    {
        return $user->isSuper();
    }
}
