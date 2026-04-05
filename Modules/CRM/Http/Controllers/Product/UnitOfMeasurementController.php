<?php

namespace Modules\CRM\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Product\UnitOfMeasurementRequest;
use Modules\CRM\Http\Resources\Product\UnitOfMeasurementResource;
use Modules\CRM\Contracts\Product\UnitOfMeasurementRepositoryInterface;

class UnitOfMeasurementController extends Controller
{
    protected $repository;

    public function __construct(UnitOfMeasurementRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $attributes = $this->repository->paginate($request->all());
        return UnitOfMeasurementResource::collection($attributes);
    }

    public function store(UnitOfMeasurementRequest $request)
    {
        $attribute = $this->repository->create($request->validated());
        return new UnitOfMeasurementResource($attribute);
    }

    public function show($id)
    {
        $attribute = $this->repository->find($id);
        return new UnitOfMeasurementResource($attribute);
    }

    public function update(UnitOfMeasurementRequest $request, $id)
    {
        $attribute = $this->repository->update($id, $request->validated());
        return new UnitOfMeasurementResource($attribute);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
