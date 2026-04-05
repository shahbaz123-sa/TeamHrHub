<?php

namespace Modules\CRM\Http\Controllers\Notice;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Notice\TypeRequest;
use Modules\CRM\Http\Resources\Notice\TypeResource;
use Modules\CRM\Contracts\Notice\TypeRepositoryInterface;

class TypeController extends Controller
{
    protected $repository;

    public function __construct(TypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $items = $this->repository->paginate($request->all());
        return TypeResource::collection($items);
    }

    public function store(TypeRequest $request)
    {
        $item = $this->repository->create($request->validated());
        return new TypeResource($item);
    }

    public function show($id)
    {
        $item = $this->repository->find($id);
        return new TypeResource($item);
    }

    public function update(TypeRequest $request, $id)
    {
        $item = $this->repository->update($id, $request->validated());
        return new TypeResource($item);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
