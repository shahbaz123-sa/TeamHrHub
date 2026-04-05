<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Auth\Http\Resources\PermissionResource;
use App\Http\Controllers\Controller;
use Modules\Auth\Http\Requests\PermissionRequest;
use Modules\Auth\Contracts\PermissionRepositoryInterface;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionRepositoryInterface $permissionRepository
    ) {}

    public function index(Request $request)
    {
        $permissions = $this->permissionRepository->all($request->all());
        return PermissionResource::collection($permissions);
    }

    public function store(PermissionRequest $request)
    {
        $permission = $this->permissionRepository->create($request->validated());
        return new PermissionResource($permission);
    }

    public function show($id)
    {
        $permission = $this->permissionRepository->find($id);
        return new PermissionResource($permission);
    }

    public function update(PermissionRequest $request, $id)
    {
        $permission = $this->permissionRepository->update($id, $request->validated());
        return new PermissionResource($permission);
    }

    public function destroy($id)
    {
        $this->permissionRepository->delete($id);
        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
