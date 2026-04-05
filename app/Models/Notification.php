<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Models\Employee;

class Notification extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'type', 'title', 'data', 'read_at', 'url'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'receiver_id', 'user_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }
}
