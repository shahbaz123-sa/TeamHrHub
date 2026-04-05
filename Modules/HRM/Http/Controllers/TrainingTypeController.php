<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Models\TrainingType;
use Modules\HRM\Http\Requests\StoreTrainingTypeRequest;
use Modules\HRM\Http\Requests\UpdateTrainingTypeRequest;
use Modules\HRM\Http\Resources\TrainingTypeResource;
use Modules\HRM\Repositories\TrainingTypeRepository;

class TrainingTypeController extends Controller
{
    public function index(TrainingTypeRepository $repo)
    {
        return TrainingTypeResource::collection($repo->all());
    }

    public function store(StoreTrainingTypeRequest $request, TrainingTypeRepository $repo)
    {
        return new TrainingTypeResource($repo->create($request->validated()));
    }

    public function show($id, TrainingTypeRepository $repo)
    {
        return new TrainingTypeResource($repo->find($id));
    }

    public function update(UpdateTrainingTypeRequest $request, $id, TrainingTypeRepository $repo)
    {
        return new TrainingTypeResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, TrainingTypeRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }
}
