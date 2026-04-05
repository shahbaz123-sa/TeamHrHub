<?php

namespace Modules\CRM\Http\Resources\Customer;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ShippingAddressResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_id' => $this->user_id,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'state' => $this->state,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'street_address' => $this->street_address,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
