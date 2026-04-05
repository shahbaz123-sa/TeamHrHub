<?php

namespace Modules\CRM\Http\Resources\Rfq;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\CRM\Http\Resources\ProductResource;

class QuotationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'rfq_id' => $this->rfq_id,
            'procs' => $this->procs,
            'due_date' => $this->due_date,
            'total_price' => $this->total_price,
            'price_per_unit' => $this->price_per_unit,
            'invoice' => $this->invoice ? Storage::disk('s3')->url($this->invoice) : '',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
