<?php

namespace Modules\Chat\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\AssignedManager;

class Message extends Model
{
    protected $connection = 'crm';

    protected $table = 'live_chat_messages';

    protected $fillable = [
        'senderId',
        'receiverId',
        'message',
        'room',
        'sessionId',
        'senderType',
        'attachments'
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class, 'room', 'roomId');
    }

    public function sender()
    {
        return $this->belongsTo(Message\User::class, 'senderId');
    }

    public function senderManager()
    {
        return $this->belongsTo(AssignedManager::class, 'senderId', 'employee_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Message\User::class, 'receiverId');
    }
}
