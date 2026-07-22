<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRoleRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->list($request->only(['search', 'per_page']));

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'from' => $users->firstItem(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'to' => $users->lastItem(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->show($id);

        $this->authorize('view', $user);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function updateRole(UpdateRoleRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->updateRole($user, $request->input('role_id'));

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'data' => $user,
        ]);
    }
}
