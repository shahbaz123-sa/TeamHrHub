<?php

namespace Modules\Auth\Contracts;

interface AuthRepositoryInterface
{
    public function register(array $data);
    public function login(array $credentials);
}
