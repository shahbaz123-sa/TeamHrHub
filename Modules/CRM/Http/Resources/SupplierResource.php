<?php

namespace Modules\CRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SupplierResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'brand' => $this->brand,
            'product_category' => $this->product_category,
            'commodity' => $this->product_category,
            'incorporation_letter' => $this->incorporation_letter ? Storage::disk('s3')->url($this->incorporation_letter) : '',
            'request_letterhead' => $this->request_letterhead ? Storage::disk('s3')->url($this->request_letterhead) : '',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'date' => Carbon::parse($this->created_at)->format('d M Y'),
        ];
    }
}
