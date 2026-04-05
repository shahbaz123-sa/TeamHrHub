<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class AuthResource extends JsonResource
{
    public function toArray($request)
    {
        /** @var User $this */
        return [
            'id' => $this->id,
            'employee_id' => $this?->employee?->id,
            'branch' => $this?->employee?->branch ?? null,
            'fullName' => $this->name,
            'username' => $this->username ?? 'unknown',
            // 'avatar' => $this->avatar ?? '/images/avatars/dummy.png',
            'avatar' => $this->avatar_url ?: '/images/avatars/dummy.png',
            'role' => $this->role,
            'roles' => $this->roles,
            'email' => $this->email,
        ];
    }
}
