<?php

namespace Modules\Chat\Repositories\Chat;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Contracts\Chat\MessageRepositoryInterface;
use Modules\Chat\Models\Chat\Message;

class MessageRepository implements MessageRepositoryInterface
{
    protected $model;

    public function __construct(Message $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model->with(['senderManager'])
            ->when(isset($filters['user_id']), function ($query) use ($filters) {
                $query->whereAny(['senderId', 'receiverId'], $filters['user_id']);
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'created_at'),
                data_get($filters, 'order_by', 'asc')
            )
            ->paginate(data_get($filters, 'per_page', 10));
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function uploadAttachment(UploadedFile $file, array $data)
    {
        $name = $file->getClientOriginalName();
        $fileNameInfo = pathinfo($name);
        $name =  str_replace(' ', '_', pathinfo($name, PATHINFO_FILENAME)) . "_" . date('Y-m-d_H-i-s') . "." . $fileNameInfo['extension'];

        $sessionId = $data['session_id'];
        $senderId = $data['sender_id'];

        $path = "web/chat-attachments/$sessionId/$senderId";

        $storage = Storage::disk('s3');

        return [
            'key' => $path . "/" . $name,
            'mimeType' => $file->getMimeType(),
            'originalName' => $name,
            'url' => $storage->url($storage->putFileAs($path, $file, $name))
        ];
    }
}
