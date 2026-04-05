<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Auth\Contracts\AuthRepositoryInterface;
use Modules\Auth\Http\Requests\RegisterRequest;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Modules\Auth\Http\Resources\AuthResource;
use Jenssegers\Agent\Agent;

class AuthController extends Controller
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authRepository->register($request->validated());
        return response()->json(new AuthResource($user), 201);
    }

    public function login(Request $request, Agent $agent)
    {
        $credentials = $request->only('email', 'password');
        $data = $this->authRepository->login($credentials);
        if (!$data) {
            return response()->json(['message' => 'Unable to login'], 401);
        }

        // Log login activity
        $location = $request->has(['latitude', 'longitude']) ? $request->latitude . ',' . $request->longitude : null;
        $tokenId = explode('|', $data['token'])[0];
        LoginActivity::create([
            'user_id' => $data['user']->id,
            'personal_access_token_id' => $tokenId,
            'device_type' => $agent->isDesktop() ? 'desktop' : ($agent->isTablet() ? 'tablet' : ($agent->isMobile() ? 'mobile' : 'unknown')),
            'location' => $location,
            'ip_address' => $request->ip(),
            'browser' => $agent->browser(),
            'user_agent'   => $request->userAgent(),
        ]);

        // Update user's last login info
        $user = $data['user'];
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        return response()->json([
            'accessToken' => $data['token'],
            'userData' => new AuthResource($data['user']),
            'userAbilityRules' => [[
                'action' => 'manage',
                'subject' => 'all'
            ]],
        ]);
    }
}
