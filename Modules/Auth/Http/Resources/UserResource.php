<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        /** @var User $this */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullName' => $this->name,
            'username' => $this->username ?? 'imrankhan',
            'avatar' => $this->avatar_url ?: '/images/avatars/dummy.png',
            'email' => $this->email,
            'user' => [
                'fullName' => $this->name,
                'email' => $this->email,
                'avatar' => $this->avatar_url ?: '/images/avatars/dummy.png',
            ],
            'role' => $this->role,
            'plan' => $this->plan,
            'billing' => [
                'email' => $this->billing_email,
                'company' => $this->company,
                'tax_id' => $this->tax_id,
            ],
            'status' => $this->status,
            'contact' => [
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
            ],
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'last_login_ip' => $this->last_login_ip,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
