<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CRM\Http\Resources\Customer\CompanyResource;
use Modules\CRM\Http\Resources\Customer\ProfileResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'profile' => $this->whenLoaded('profile', new ProfileResource($this->profile)),
            'company' => $this->whenLoaded('company', new CompanyResource($this->company)),
            'latest_message' => $this->resource->latestMessage($this->id),
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'type' => $this->type,
            'is_verified' => $this->is_verified,
            'is_privacy' => $this->is_privacy,
            'is_temp_valid' => $this->is_temp_valid,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
