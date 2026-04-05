<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\HRM\Models\JobStage;
use Modules\HRM\Http\Requests\StoreJobStageRequest;
use Modules\HRM\Http\Requests\UpdateJobStageRequest;
use Modules\HRM\Http\Resources\JobStageResource;
use Modules\HRM\Repositories\JobStageRepository;

class JobStageController extends Controller
{
    public function index(JobStageRepository $repo)
    {
        return JobStageResource::collection($repo->all());
    }

    public function store(StoreJobStageRequest $request, JobStageRepository $repo)
    {
        return new JobStageResource($repo->create($request->validated()));
    }

    public function show($id, JobStageRepository $repo)
    {
        return new JobStageResource($repo->find($id));
    }

    public function update(UpdateJobStageRequest $request, $id, JobStageRepository $repo)
    {
        return new JobStageResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, JobStageRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }
}
