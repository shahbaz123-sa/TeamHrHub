<?php

namespace Modules\CRM\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Product\BrandRequest;
use Modules\CRM\Http\Resources\Product\BrandResource;
use Modules\CRM\Contracts\Product\BrandRepositoryInterface;

class BrandController extends Controller
{
    protected $repository;

    public function __construct(BrandRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $brands = $this->repository->paginate($request->all());
        return BrandResource::collection($brands);
    }

    public function store(BrandRequest $request)
    {
        $brand = $this->repository->create($request->validated());
        return new BrandResource($brand);
    }

    public function show($id)
    {
        $brand = $this->repository->find($id);
        return new BrandResource($brand);
    }

    public function update(BrandRequest $request, $id)
    {
        $brand = $this->repository->update($id, $request->validated());
        return new BrandResource($brand);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
