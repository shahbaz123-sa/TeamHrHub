<?php

namespace Modules\Auth\Models;

use App\Models\User as ModelsUser;

class User extends ModelsUser
{
    protected $table = 'users';
}
