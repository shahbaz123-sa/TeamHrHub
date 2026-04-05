<?php

namespace Modules\CRM\Repositories\Wordpress;

use Illuminate\Support\Facades\Http;
use Modules\CRM\Contracts\Wordpress\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function login(array $credentials)
    {
        $response = Http::timeout(30)
            ->post(config('woocommerce.store_url') . "/wp-json/jwt-auth/v1/token", [
                'username' => $credentials['username'],
                'password' => $credentials['password'],
            ]);

        $responseData = $response->json();
        $isSuccess = data_get($responseData, 'success', false);
        $isSuccessCode = data_get($responseData, 'statusCode', 400) == 200;
        $message = data_get($responseData, 'message', data_get($responseData, 'code', 'Authentication failed'));

        if (!$isSuccess || !$isSuccessCode) {
            return [
                'status' => false,
                'message' => $message,
            ];
        }

        return [
            'status' => true,
            'message' => $message,
        ] + $credentials;
    }
}
