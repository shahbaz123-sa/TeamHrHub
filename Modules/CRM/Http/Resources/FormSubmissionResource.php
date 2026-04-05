<?php

namespace Modules\CRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class FormSubmissionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'form_type' => $this->form_type,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'location' => $this->location,
            'company_name' => $this->company_name,
            'company_type' => $this->company_type,
            'commodity' => $this->category->name ?? Str::ucfirst($this->commodity),
            'product_required' => $this->product->name ?? Str::ucfirst($this->product_required),
            'quantity' => $this->quantity_required . " " . $this->uom_name,
            'quantity_required' => $this->quantity_required,
            'payment_method' => $this->payment_method,
            'credit_term' => $this->credit_term,
            'preferred_date' => $this->preferred_date ? Carbon::parse($this->preferred_date)->format('d M Y') : null,
            'delivery_location' => $this->delivery_location,
            'interested_in' => $this->interested_in,
            'technical_specs' => $this->technical_specs,
            'message' => $this->message,
            'privacy_ask' => $this->privacy_ask,
            'user_type' => $this->user_type,
            'industry_type' => $this->industry_type,
            'uom_name' => $this->uom_name,
            'date' => $this->created_at ? Carbon::parse($this->created_at)->format('d M Y') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
