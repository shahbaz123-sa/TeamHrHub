<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;

use Modules\Auth\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected UserService $userService
    ) {}

    public function index(Request $request)
    {
        $users = $this->userRepository->paginate([
            'q' => $request->query('q'),
            'status' => $request->query('status'),
            'role' => $request->query('role'),
            'plan' => $request->query('plan'),
            'sortBy' => $request->query('sortBy'),
            'orderBy' => $request->query('orderBy'),
        ], $request->query('itemsPerPage', 10));

        return response()->json([
            'users' => UserResource::collection($users),
            'totalUsers' => $users->total(),
            'roles' => $this->userRepository->getRoles(),
            'plans' => $this->userRepository->getPlans(),
            'statuses' => $this->userRepository->getStatuses(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userRepository->create($request->validated());

        return response()->json([
            'message' => 'User created successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    public function show(string $id)
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $updated = $this->userRepository->update($id, $request->validated());

        if (!$updated) {
            return response()->json(['message' => 'User update failed'], 500);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource($this->userRepository->find($id)),
        ]);
    }

    public function destroy(int $id)
    {
        $deleted = $this->userRepository->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'User deletion failed'], 500);
        }

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function stats()
    {
        try {
            $stats = $this->userRepository->getStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignRoles(Request $request, $userId)
    {
        $request->validate(['roles' => 'required|array']);
        $user = $this->userRepository->find($userId);
        $user->syncRoles($request->roles);
        return new UserResource($user->load('roles'));
    }

    public function revokeRoles(Request $request, $userId)
    {
        $request->validate(['roles' => 'required|array']);
        $user = $this->userRepository->find($userId);
        $user->removeRole($request->roles);
        return new UserResource($user->load('roles'));
    }

    public function getLoggedInUserPermissions()
    {
        return response([
            'status' => true,
            'data' => auth()->user()->getAllPermissions()->pluck('name')
        ]);
    }

    public function profile()
    {
        try {
            $result = $this->userService->getProfile();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(UpdateUserRequest $request)
    {
        try {
            $result = $this->userService->updateProfile($request);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function forceLogout(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
        ]);

        PersonalAccessToken::whereIn('tokenable_id', $request->user_ids)
            ->where('tokenable_type', User::class)
            ->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Users logged out successfully']);
    }
}
