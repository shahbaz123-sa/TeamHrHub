<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Contracts\DependentRepositoryInterface;
use Modules\HRM\Http\Resources\DependentResource;
use Modules\HRM\Http\Requests\StoreDependentRequest;
use Modules\HRM\Http\Requests\UpdateDependentRequest;

class DependentController extends Controller
{
    protected $repository;

    public function __construct(DependentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index($employeeId)
    {
        $dependents = $this->repository->getByEmployee($employeeId);
        return DependentResource::collection($dependents);
    }

    public function store(StoreDependentRequest $request, $employeeId)
    {
        $data = array_merge($request->validated(), ['employee_id' => $employeeId]);
        $dependent = $this->repository->create($data);
        return new DependentResource($dependent);
    }

    public function show($employeeId, $id)
    {
        $dependent = $this->repository->find($id);
        return new DependentResource($dependent);
    }

    public function update(UpdateDependentRequest $request, $employeeId, $id)
    {
        $dependent = $this->repository->update($id, $request->validated());
        return new DependentResource($dependent);
    }

    public function destroy($employeeId, $id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
