<?php

namespace Modules\Chat\Models\Chat;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $connection = 'crm';

    protected $table = 'chat_sessions';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'roomId',
        'purpose',
        'referenceId',
        'customerId',
        'status',
    ];

    public function participants()
    {
        return $this->hasMany(Participant::class, 'sessionId');
    }
}
