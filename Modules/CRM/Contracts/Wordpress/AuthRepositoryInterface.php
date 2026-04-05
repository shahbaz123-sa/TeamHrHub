<?php

namespace Modules\CRM\Contracts\Wordpress;

interface AuthRepositoryInterface
{
    public function login(array $credentials);
}
