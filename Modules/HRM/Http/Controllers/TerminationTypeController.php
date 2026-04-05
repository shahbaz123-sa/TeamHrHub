<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\StoreTerminationTypeRequest;
use Modules\HRM\Http\Requests\UpdateTerminationTypeRequest;
use Modules\HRM\Http\Resources\TerminationTypeResource;
use Modules\HRM\Repositories\TerminationTypeRepository;

class TerminationTypeController extends Controller
{
    protected $repository;

    public function __construct(TerminationTypeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return TerminationTypeResource::collection($this->repository->all());
    }

    public function store(StoreTerminationTypeRequest $request)
    {
        return new TerminationTypeResource($this->repository->create($request->validated()));
    }

    public function show($id)
    {
        return new TerminationTypeResource($this->repository->find($id));
    }

    public function update(UpdateTerminationTypeRequest $request, $id)
    {
        return new TerminationTypeResource($this->repository->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
