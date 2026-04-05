<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Models\JobCategory;
use Modules\HRM\Http\Requests\StoreJobCategoryRequest;
use Modules\HRM\Http\Requests\UpdateJobCategoryRequest;
use Modules\HRM\Http\Resources\JobCategoryResource;
use Modules\HRM\Repositories\JobCategoryRepository;

class JobCategoryController extends Controller
{
    public function index(JobCategoryRepository $repo)
    {
        return JobCategoryResource::collection($repo->all());
    }

    public function store(StoreJobCategoryRequest $request, JobCategoryRepository $repo)
    {
        return new JobCategoryResource($repo->create($request->validated()));
    }

    public function show($id, JobCategoryRepository $repo)
    {
        return new JobCategoryResource($repo->find($id));
    }

    public function update(UpdateJobCategoryRequest $request, $id, JobCategoryRepository $repo)
    {
        return new JobCategoryResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, JobCategoryRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }
}
