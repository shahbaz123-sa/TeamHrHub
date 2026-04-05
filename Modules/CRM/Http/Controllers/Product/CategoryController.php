<?php

namespace Modules\CRM\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Product\CategoryRequest;
use Modules\CRM\Http\Resources\Product\CategoryResource;
use Modules\CRM\Contracts\Product\CategoryRepositoryInterface;

class CategoryController extends Controller
{
    protected $repository;

    public function __construct(CategoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $categories = $this->repository->paginate($request->all());
        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->repository->create($request->validated());
        return new CategoryResource($category);
    }

    public function show($id)
    {
        $category = $this->repository->find($id);
        return new CategoryResource($category);
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = $this->repository->update($id, $request->validated());
        return new CategoryResource($category);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }

    public function getParents(Request $request)
    {
        return CategoryResource::collection($this->repository->getCategoryParents($request->all()));
    }
}
