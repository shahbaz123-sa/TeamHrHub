<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\BranchResource;
use Modules\HRM\Http\Requests\StoreBranchRequest;
use Modules\HRM\Http\Requests\UpdateBranchRequest;
use Modules\HRM\Contracts\BranchRepositoryInterface;

class BranchController extends Controller
{
    protected $repository;

    public function __construct(BranchRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $branches = $this->repository->getAll($request->all());
        return BranchResource::collection($branches);
    }

    public function store(StoreBranchRequest $request)
    {
        $branch = $this->repository->create($request->validated());
        return new BranchResource($branch);
    }

    public function show($id)
    {
        $branch = $this->repository->find($id);
        return new BranchResource($branch);
    }

    public function update(UpdateBranchRequest $request, $id)
    {
        $branch = $this->repository->update($id, $request->validated());
        return new BranchResource($branch);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }

    public function allowRemote()
    {
        $this->repository->allowRemote();
        return response()->json([
            'message' => 'Remote attendance enabled for branch.',
        ]);
    }

    public function disallowRemote()
    {
        $this->repository->disallowRemote();
        return response()->json([
            'message' => 'Remote attendance disabled for branch.',
        ]);
    }
}
