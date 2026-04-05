<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    protected $fillable = [
        'user_id',
        'device_type',
        'location',
        'ip_address',
        'browser',
        'user_agent',
        'personal_access_token_id',
    ];

    public function token()
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }
}
