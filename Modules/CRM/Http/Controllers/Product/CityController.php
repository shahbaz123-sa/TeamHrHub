<?php

namespace Modules\CRM\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Product\CityRequest;
use Modules\CRM\Http\Resources\Product\CityResource;
use Modules\CRM\Contracts\Product\CityRepositoryInterface;

class CityController extends Controller
{
    protected $repository;

    public function __construct(CityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $cities = $this->repository->paginate($request->all());
        return CityResource::collection($cities);
    }

    public function store(CityRequest $request)
    {
        $city = $this->repository->create($request->validated());
        return new CityResource($city);
    }

    public function show($id)
    {
        $city = $this->repository->find($id);
        return new CityResource($city);
    }

    public function update(CityRequest $request, $id)
    {
        $city = $this->repository->update($id, $request->validated());
        return new CityResource($city);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
