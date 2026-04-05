<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use App\Models\LoginActivity;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $dates = ['revoked_at'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('revoked', function ($builder) {
            $builder->whereNull('revoked_at');
        });
    }

    public function loginActivity()
    {
        return $this->hasOne(LoginActivity::class, 'personal_access_token_id', 'id')->latestOfMany('created_at');
    }
}
