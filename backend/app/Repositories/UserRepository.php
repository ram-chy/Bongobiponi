<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with('role');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', "%{$filters['search']}%")
                  ->orWhere('last_name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function findById(int $id): ?User
    {
        return User::with('role')->find($id);
    }

    public function updateRole(User $user, int $roleId): User
    {
        $user->update(['role_id' => $roleId]);

        return $user->load('role');
    }

    public function findOrFail(int $id): User
    {
        return User::with('role')->findOrFail($id);
    }
}
