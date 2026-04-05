<?php

namespace Modules\CRM\Http\Resources\Customer;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'profile_image' => $this->profile_image ? Storage::disk('s3')->url($this->profile_image) : null,
            'full_name' => $this->first_name . " " . $this->last_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'nationality' => $this->nationality,
            'date_of_birth' => date('Y-m-d', strtotime($this->date_of_birth)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
