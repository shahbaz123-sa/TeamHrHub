<?php

namespace Modules\CRM\Http\Controllers\Product\Attribute;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Product\Attribute\ValueRequest;
use Modules\CRM\Http\Resources\Product\Attribute\ValueResource;
use Modules\CRM\Contracts\Product\Attribute\ValueRepositoryInterface;

class ValueController extends Controller
{
    protected $repository;

    public function __construct(ValueRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $values = $this->repository->paginate($request->all());
        return ValueResource::collection($values);
    }

    public function store(ValueRequest $request)
    {
        $value = $this->repository->create($request->validated());
        return new ValueResource($value);
    }

    public function show($id)
    {
        $value = $this->repository->find($id);
        return new ValueResource($value);
    }

    public function update(ValueRequest $request, $id)
    {
        $value = $this->repository->update($id, $request->validated());
        return new ValueResource($value);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
