<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\ProductRepositoryInterface;
use Modules\CRM\Http\Requests\ProductRequest;
use Modules\CRM\Http\Resources\ProductResource;

class ProductController extends Controller
{
    protected $repository;

    public function __construct(ProductRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $categories = $this->repository->paginate($request->all());
        return ProductResource::collection($categories);
    }

    public function store(ProductRequest $request)
    {
        $category = $this->repository->create($request->validated());
        return new ProductResource($category);
    }

    public function show($id)
    {
        $category = $this->repository->find($id);
        return new ProductResource($category);
    }

    public function update(ProductRequest $request, $id)
    {
        $category = $this->repository->update($id, $request->validated());
        return new ProductResource($category);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }

    public function getParents(Request $request)
    {
        return ProductResource::collection($this->repository->getParents($request->all()));
    }
}
