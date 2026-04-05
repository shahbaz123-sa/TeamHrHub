<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HRM\Models\Designation;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\StoreDesignationRequest;
use Modules\HRM\Http\Requests\UpdateDesignationRequest;
use Modules\HRM\Http\Resources\DesignationResource;
use Modules\HRM\Contracts\DesignationRepositoryInterface;

class DesignationController extends Controller
{
    protected $repository;

    public function __construct(DesignationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }


    public function index(Request $request)
    {
        $designations = $this->repository->getAll($request->all());
        return DesignationResource::collection($designations);
    }

    public function store(StoreDesignationRequest $request)
    {
        $designation = $this->repository->create($request->validated());
        return new DesignationResource($designation);
    }

    public function show(Designation $designation)
    {
        return new DesignationResource($designation);
    }

    public function update(UpdateDesignationRequest $request, Designation $designation)
    {
        $designation = $this->repository->update($designation->id, $request->validated());
        return new DesignationResource($designation);
    }

    public function destroy(Designation $designation)
    {
        $this->repository->delete($designation->id);
        return response()->noContent();
    }
}
