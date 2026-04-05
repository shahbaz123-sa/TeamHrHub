<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\AwardTypeResource;
use Modules\HRM\Http\Requests\StoreAwardTypeRequest;
use Modules\HRM\Http\Requests\UpdateAwardTypeRequest;
use Modules\HRM\Contracts\AwardTypeRepositoryInterface;

class AwardTypeController extends Controller
{
    protected $repository;

    public function __construct(AwardTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        return AwardTypeResource::collection($this->repository->all($request->all()));
    }

    public function store(StoreAwardTypeRequest $request)
    {
        return new AwardTypeResource($this->repository->create($request->validated()));
    }

    public function show($id)
    {
        return new AwardTypeResource($this->repository->find($id));
    }

    public function update(UpdateAwardTypeRequest $request, $id)
    {
        return new AwardTypeResource($this->repository->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
