<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessWarehouse();
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->canAccessWarehouse();
    }

    public function create(User $user): bool
    {
        return $user->canAccessWarehouse();
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->canAccessWarehouse();
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->canAccessWarehouse();
    }

    public function restore(User $user, Warehouse $warehouse): bool
    {
        return false;
    }

    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        return $user->isSuper();
    }
}
