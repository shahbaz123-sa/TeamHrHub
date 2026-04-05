<?php

namespace Modules\Chat\Http\Requests\Chat;

use Modules\CRM\Http\Requests\BaseFormRequest;

class MessageRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('chat.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('chat.update');
        }
        return $user && $user->can('chat.read');
    }

    public function rules()
    {
        return [
            'senderId' => 'required|integer',
            'receiverId' => 'required|integer|exists:crm.user,id',
            'message' => 'required|string',
            'room' => 'required|string',
            'sessionId' => 'required|integer',
            'senderType' => 'required|string',
        ];
    }
}
