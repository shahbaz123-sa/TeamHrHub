<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Contracts\AssetTypeRepositoryInterface;
use Modules\HRM\Http\Requests\AssetTypeRequest;
use Modules\HRM\Http\Resources\AssetTypeResource;

class AssetTypeController extends Controller
{
    protected $repository;

    public function __construct(AssetTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $categories = $this->repository->getAll();
        return AssetTypeResource::collection($categories);
    }

    public function store(AssetTypeRequest $request)
    {
        $category = $this->repository->create($request->validated());
        return new AssetTypeResource($category);
    }

    public function show($id)
    {
        $category = $this->repository->find($id);
        return new AssetTypeResource($category);
    }

    public function update(AssetTypeRequest $request, $id)
    {
        $category = $this->repository->update($id, $request->validated());
        return new AssetTypeResource($category);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
