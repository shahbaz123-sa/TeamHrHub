<?php

namespace Modules\HRM\Http\Controllers;

use Modules\HRM\Models\GoalType;
use App\Http\Controllers\Controller;

use Modules\HRM\Http\Requests\StoreGoalTypeRequest;
use Modules\HRM\Http\Requests\UpdateGoalTypeRequest;
use Modules\HRM\Http\Resources\GoalTypeResource;
use Modules\HRM\Repositories\GoalTypeRepository;

class GoalTypeController extends Controller
{
    public function index(GoalTypeRepository $repo)
    {
        return GoalTypeResource::collection($repo->all());
    }

    public function store(StoreGoalTypeRequest $request, GoalTypeRepository $repo)
    {
        return new GoalTypeResource($repo->create($request->validated()));
    }

    public function show($id, GoalTypeRepository $repo)
    {
        return new GoalTypeResource($repo->find($id));
    }

    public function update(UpdateGoalTypeRequest $request, $id, GoalTypeRepository $repo)
    {
        return new GoalTypeResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, GoalTypeRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }
}
