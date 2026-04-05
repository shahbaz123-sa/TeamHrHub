<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\DeductionResource;
use Modules\HRM\Http\Requests\StoreDeductionRequest;
use Modules\HRM\Http\Requests\UpdateDeductionRequest;
use Modules\HRM\Contracts\DeductionRepositoryInterface;

class DeductionController extends Controller
{
    protected $repository;

    public function __construct(DeductionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $deductions = $this->repository->getAll($request->all());
        return DeductionResource::collection($deductions);
    }

    public function store(StoreDeductionRequest $request)
    {
        $deduction = $this->repository->create($request->validated());
        return new DeductionResource($deduction);
    }

    public function show($id)
    {
        $deduction = $this->repository->find($id);
        return new DeductionResource($deduction);
    }

    public function update(UpdateDeductionRequest $request, $id)
    {
        $deduction = $this->repository->update($id, $request->validated());
        return new DeductionResource($deduction);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
