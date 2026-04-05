<?php

namespace Modules\Chat\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class   MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'sender_manager' => $this->whenLoaded('senderManager', isset($this->senderManager) ? [
                'id' => data_get($this, 'senderManager.id'),
                'employee_id' => data_get($this, 'senderManager.employee_id'),
                'name' => data_get($this, 'senderManager.name'),
                'role' => data_get($this, 'senderManager.role'),
                'avatar' => isset($this->senderManager->profileImage) ? Storage::disk('s3')->url($this->senderManager->profileImage) : null,
            ] : null),
            'message' => $this->message,
            'room' => $this->room,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'session_id' => $this->sessionId,
            'sender_type' => $this->senderType,
            'attachments' => $this->attachments,
            'attachment_urls' => collect($this->attachments)->map(function ($attachment) {
                $path = data_get($attachment, 'key');
                if ($path) {
                    return Storage::disk('s3')->url($path);
                }
                return null;
            })->filter(),
        ];
    }
}
