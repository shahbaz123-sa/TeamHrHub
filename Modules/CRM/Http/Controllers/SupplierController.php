<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\SupplierRepositoryInterface;
use Modules\CRM\Http\Resources\SupplierResource;

class SupplierController extends Controller
{
    protected $repository;

    public function __construct(SupplierRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $data = $this->repository->paginate($request->all());
        return SupplierResource::collection($data);
    }

    public function show($id)
    {
        $supplier = $this->repository->find($id);
        return new SupplierResource($supplier);
    }

    public function store(Request $request)
    {
        $supplier = $this->repository->create($request->validated());
        return new SupplierResource($supplier);
    }

    public function update(Request $request, $id)
    {
        $supplier = $this->repository->update($id, $request->validated());
        return new SupplierResource($supplier);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
