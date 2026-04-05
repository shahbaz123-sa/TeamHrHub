<?php

namespace Modules\CRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        $orders = $this->orders();

        return [
            'id' => $this->id,
            'profile' => $this->whenLoaded('profile', new Customer\ProfileResource($this->profile)),
            'company' => $this->whenLoaded('company', new Customer\CompanyResource($this->company)),
            'shipping_addresses' => $this->whenLoaded('shippingAddresses', Customer\ShippingAddressResource::collection($this->shippingAddresses)),
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'type' => $this->type,
            'is_verified' => $this->is_verified,
            'is_privacy' => $this->is_privacy,
            'is_temp_valid' => $this->is_temp_valid,
            'status' => $this->status,
            'total_orders' => $orders->count(),
            'total_orders_amount' => number_format($orders->where('user_id', $this->id)->sum('total_amount'), 2),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
