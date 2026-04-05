<?php

namespace Modules\CRM\Http\Controllers\Post;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Post\TagRequest;
use Modules\CRM\Http\Resources\Post\TagResource;
use Modules\CRM\Contracts\Post\TagRepositoryInterface;

class TagController extends Controller
{
    protected $repository;

    public function __construct(TagRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $tags = $this->repository->paginate($request->all());
        return TagResource::collection($tags);
    }

    public function store(TagRequest $request)
    {
        $tag = $this->repository->create($request->validated());
        return new TagResource($tag);
    }

    public function show($id)
    {
        $tag = $this->repository->find($id);
        return new TagResource($tag);
    }

    public function update(TagRequest $request, $id)
    {
        $tag = $this->repository->update($id, $request->validated());
        return new TagResource($tag);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
