<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->paginate($filters);
    }

    public function show(int $id): User
    {
        return $this->userRepository->findOrFail($id);
    }

    public function updateRole(User $user, int $roleId): User
    {
        if ($user->id === auth()->id()) {
            throw new \RuntimeException('You cannot change your own role.');
        }

        return $this->userRepository->updateRole($user, $roleId);
    }
}
