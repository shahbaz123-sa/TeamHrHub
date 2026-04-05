<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthRepository implements AuthRepositoryInterface
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $user->assignRole('user');

        return $user;
    }

    public function login(array $credentials)
    {
        $email = strtolower(trim($credentials['email'] ?? ''));
        $password = $credentials['password'] ?? '';

        // 1) Email not found
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email does not exist.'],
            ]);
        }else if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Incorrect password.'],
            ]);
        }
        Auth::login($user);
        $user = Auth::user();

        $token = $user->createToken('api-token')->plainTextToken;
        return [
            'user' => $user,
            'token' => $token
        ];
    }
}
