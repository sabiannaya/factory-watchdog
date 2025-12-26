<?php

namespace App\Policies;

use App\Models\Production;
use App\Models\User;

class ProductionPolicy
{
    /**
     * Determine whether the user can view any models.
     * All authenticated users can view the list (filtered by their access).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Super can view all. Staff can only view assigned productions.
     */
    public function view(User $user, Production $production): bool
    {
        return $user->canAccessProduction($production->production_id);
    }

    /**
     * Determine whether the user can create models.
     * Only Super can create new productions.
     */
    public function create(User $user): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can update the model.
     * Super can update all. Staff can update assigned productions.
     */
    public function update(User $user, Production $production): bool
    {
        return $user->canAccessProduction($production->production_id);
    }

    /**
     * Determine whether the user can delete the model.
     * Only Super can delete.
     */
    public function delete(User $user, Production $production): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Production $production): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Production $production): bool
    {
        return $user->isSuper();
    }
}
