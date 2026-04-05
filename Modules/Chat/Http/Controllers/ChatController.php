<?php

namespace Modules\Chat\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Chat\Http\Resources\UserResource;
use Modules\Chat\Contracts\ChatRepositoryInterface;

class ChatController extends Controller
{
    protected $repository;

    public function __construct(ChatRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $data = $this->repository->paginate($request->all());
        return UserResource::collection($data);
    }
}
