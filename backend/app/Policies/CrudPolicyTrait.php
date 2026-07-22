<?php

namespace App\Policies;

use App\Models\User;

trait CrudPolicyTrait
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function view(User $user, mixed $model): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return $model->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, mixed $model): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return $model->created_by === $user->id;
    }

    public function delete(User $user, mixed $model): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, mixed $model): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, mixed $model): bool
    {
        return $user->hasRole('admin');
    }
}
