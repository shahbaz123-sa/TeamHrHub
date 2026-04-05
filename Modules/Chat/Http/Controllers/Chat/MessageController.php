<?php

namespace Modules\Chat\Http\Controllers\Chat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Chat\Http\Resources\Chat\MessageResource;
use Modules\Chat\Contracts\Chat\MessageRepositoryInterface;
use Modules\Chat\Http\Requests\Chat\MessageRequest;

class MessageController extends Controller
{
    protected $repository;

    public function __construct(MessageRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $data = $this->repository->paginate($request->all());
        return MessageResource::collection($data);
    }

    public function store(MessageRequest $request)
    {
        $message = $this->repository->create($request->validated());
        return new MessageResource($message);
    }

    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'attachment' => 'required|file',
            'session_id' => 'required|gt:0',
            'sender_id' => 'required|gt:0',
        ]);

        return $this->repository->uploadAttachment($request->attachment, $request->all());
    }
}
