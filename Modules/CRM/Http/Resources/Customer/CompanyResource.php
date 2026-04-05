<?php

namespace Modules\CRM\Http\Resources\Customer;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CRM\Http\Resources\CustomerResource;

class CompanyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $this->company_name,
            'company_image' => $this->company_image ? Storage::disk('s3')->url($this->company_image) : '',
            'national_tax_number' => $this->national_tax_number,
            'company_address' => $this->company_address,
            'incorporation' => $this->incorporation,
            'industry_type' => $this->industry_type,
            'status' => $this->status,
            'cnic_number' => $this->cnic_number,
            'company_type' => $this->company_type,
            'documents' => $this->whenLoaded('documents', Company\DocumentResource::collection($this->documents)),
            'contact' => $this->whenLoaded('contact', new Company\ContactResource($this->contact)),
            'customer' => $this->whenLoaded('customer', new CustomerResource($this->customer)),
        ];
    }
}
