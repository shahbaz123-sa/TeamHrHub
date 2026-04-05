<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Models\PerformanceType;
use Modules\HRM\Http\Requests\StorePerformanceTypeRequest;
use Modules\HRM\Http\Requests\UpdatePerformanceTypeRequest;
use Modules\HRM\Http\Resources\PerformanceTypeResource;
use Modules\HRM\Repositories\PerformanceTypeRepository;

class PerformanceTypeController extends Controller
{
    public function index(PerformanceTypeRepository $repo)
    {
        return PerformanceTypeResource::collection($repo->all());
    }

    public function store(StorePerformanceTypeRequest $request, PerformanceTypeRepository $repo)
    {
        return new PerformanceTypeResource($repo->create($request->validated()));
    }

    public function show($id, PerformanceTypeRepository $repo)
    {
        return new PerformanceTypeResource($repo->find($id));
    }

    public function update(UpdatePerformanceTypeRequest $request, $id, PerformanceTypeRepository $repo)
    {
        return new PerformanceTypeResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, PerformanceTypeRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }
}
