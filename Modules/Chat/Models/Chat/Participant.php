<?php

namespace Modules\Chat\Models\Chat;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $connection = 'crm';

    protected $table = 'chat_participants';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'sessionId',
        'userType',
        'userId',
        'joinedAt',
        'leftAt',
    ];
}
