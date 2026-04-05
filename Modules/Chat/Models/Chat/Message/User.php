<?php

namespace Modules\Chat\Models\Chat\Message;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Modules\Chat\Http\Resources\Chat\MessageResource;
use Modules\Chat\Models\Chat\Message;
use Modules\CRM\Models\Customer\Company;
use Modules\CRM\Models\Customer\Profile;

class User extends Model
{
    use Notifiable;

    protected $connection = 'crm';

    protected $table = 'user';

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone_number',
        'type',
        'is_verified',
        'is_privacy',
        'created_at',
        'updated_at',
        'status',
        'is_temp_valid',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'senderId');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiverId');
    }

    public function latestMessage($senderId)
    {
        return new MessageResource(Message::whereAny(['receiverId', 'senderId'], $senderId)
            ->orderByDesc('id')
            ->first());
    }
}
