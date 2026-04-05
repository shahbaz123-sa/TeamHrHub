<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\DepartmentRequest;
use Modules\HRM\Http\Resources\DepartmentResource;
use Modules\HRM\Contracts\DepartmentRepositoryInterface;

class DepartmentController extends Controller
{
    protected $repository;

    public function __construct(DepartmentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $departments = $this->repository->getAll($request->all());
        return DepartmentResource::collection($departments);
    }

    public function store(DepartmentRequest $request)
    {
        $department = $this->repository->create($request->validated());
        return new DepartmentResource($department);
    }

    public function show($id)
    {
        $department = $this->repository->find($id);
        return new DepartmentResource($department);
    }

    public function update(DepartmentRequest $request, $id)
    {
        $department = $this->repository->update($id, $request->validated());
        return new DepartmentResource($department);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
