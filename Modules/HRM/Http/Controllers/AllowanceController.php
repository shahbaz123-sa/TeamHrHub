<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\AllowanceResource;
use Modules\HRM\Http\Requests\StoreAllowanceRequest;
use Modules\HRM\Http\Requests\UpdateAllowanceRequest;
use Modules\HRM\Contracts\AllowanceRepositoryInterface;

class AllowanceController extends Controller
{
    protected $repository;

    public function __construct(AllowanceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $allowances = $this->repository->getAll($request->all());
        return AllowanceResource::collection($allowances);
    }

    public function store(StoreAllowanceRequest $request)
    {
        $allowance = $this->repository->create($request->validated());
        return new AllowanceResource($allowance);
    }

    public function show($id)
    {
        $allowance = $this->repository->find($id);
        return new AllowanceResource($allowance);
    }

    public function update(UpdateAllowanceRequest $request, $id)
    {
        $allowance = $this->repository->update($id, $request->validated());
        return new AllowanceResource($allowance);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
