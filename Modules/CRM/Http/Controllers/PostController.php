<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\PostRepositoryInterface;
use Modules\CRM\Http\Requests\PostRequest;
use Modules\CRM\Http\Resources\PostResource;

class PostController extends Controller
{
    protected $repository;

    public function __construct(PostRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $posts = $this->repository->paginate($request->all());
        return PostResource::collection($posts);
    }

    public function store(PostRequest $request)
    {
        $post = $this->repository->create($request->validated());
        return new PostResource($post);
    }

    public function show($id)
    {
        $post = $this->repository->find($id);
        return new PostResource($post);
    }

    public function update(PostRequest $request, $id)
    {
        $post = $this->repository->update($id, $request->validated());
        return new PostResource($post);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
