<?php

namespace Modules\CRM\Http\Resources\Rfq;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CRM\Http\Resources\ProductResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'rfq_id' => $this->rfq_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', new ProductResource($this->product)),
            'product_name' => $this->product_name,
            'variation_id' => $this->variation_id,
            'variation' => $this->whenLoaded('variation', new ProductResource($this->variation)),
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'technical_specs' => $this->technical_specs,
            'created_at' => $this->created_at,
        ];
    }
}
