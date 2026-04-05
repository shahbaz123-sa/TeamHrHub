<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\NoticeRequest;
use Modules\CRM\Http\Resources\NoticeResource;
use Modules\CRM\Contracts\NoticeRepositoryInterface;

class NoticeController extends Controller
{
    protected $repository;

    public function __construct(NoticeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $items = $this->repository->paginate($request->all());
        return NoticeResource::collection($items);
    }

    public function store(NoticeRequest $request)
    {
        $item = $this->repository->create($request->validated());
        return new NoticeResource($item);
    }

    public function show($id)
    {
        $item = $this->repository->find($id);
        return new NoticeResource($item);
    }

    public function update(NoticeRequest $request, $id)
    {
        $item = $this->repository->update($id, $request->validated());
        return new NoticeResource($item);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
