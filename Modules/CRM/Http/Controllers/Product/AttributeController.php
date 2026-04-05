<?php

namespace Modules\CRM\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Product\AttributeRequest;
use Modules\CRM\Http\Resources\Product\AttributeResource;
use Modules\CRM\Contracts\Product\AttributeRepositoryInterface;

class AttributeController extends Controller
{
    protected $repository;

    public function __construct(AttributeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $attributes = $this->repository->paginate($request->all());
        return AttributeResource::collection($attributes);
    }

    public function store(AttributeRequest $request)
    {
        $attribute = $this->repository->create($request->validated());
        return new AttributeResource($attribute);
    }

    public function show($id)
    {
        $attribute = $this->repository->find($id);
        return new AttributeResource($attribute);
    }

    public function update(AttributeRequest $request, $id)
    {
        $attribute = $this->repository->update($id, $request->validated());
        return new AttributeResource($attribute);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
