<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Auth\Http\Resources\RoleResource;
use App\Http\Controllers\Controller;
use Modules\Auth\Http\Requests\RoleRequest;
use Modules\Auth\Contracts\RoleRepositoryInterface;

class RoleController extends Controller
{
    protected $repository;

    public function __construct(RoleRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $roles = isset($request->for_role_assignment)
            ? $this->repository->all($request->all())
            : $this->repository->paginate($request->all());

        return RoleResource::collection($roles);
    }

    public function store(RoleRequest $request)
    {
        $role = $this->repository->create($request->validated());
        return new RoleResource($role->load('permissions'));
    }

    public function show(string $id)
    {
        $role = $this->repository->find($id);
        return new RoleResource($role);
    }

    public function update(RoleRequest $request, string $id)
    {
        $role = $this->repository->update($id, $request->validated());

        if (!$role) {
            return response()->json(['message' => 'Failed to update role'], 500);
        }

        return new RoleResource($role);
    }

    public function destroy(string $id)
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Failed to delete role'], 500);
        }

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function getPermissions()
    {
        $permissions = $this->repository->getPermissions();
        return response()->json(['data' => $permissions]);
    }
}
