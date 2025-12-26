<?php

namespace App\Policies;

use App\Models\MachineGroup;
use App\Models\User;

class MachineGroupPolicy
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
     * Super can view all. Staff can only view machine groups attached to their assigned productions.
     */
    public function view(User $user, MachineGroup $machineGroup): bool
    {
        return $user->canAccessMachineGroup($machineGroup->machine_group_id);
    }

    /**
     * Determine whether the user can create models.
     * Only Super can create new machine groups.
     */
    public function create(User $user): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can update the model.
     * Only Super can update machine groups (staff can only view).
     */
    public function update(User $user, MachineGroup $machineGroup): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can delete the model.
     * Only Super can delete.
     */
    public function delete(User $user, MachineGroup $machineGroup): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MachineGroup $machineGroup): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MachineGroup $machineGroup): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can attach this machine group to a production.
     */
    public function attach(User $user, MachineGroup $machineGroup): bool
    {
        return $user->isSuper();
    }

    /**
     * Determine whether the user can detach this machine group from a production.
     */
    public function detach(User $user, MachineGroup $machineGroup): bool
    {
        return $user->isSuper();
    }
}
