<?php

namespace Modules\CRM\Http\Resources\Product;

use Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class GraphPriceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'category_name' => $this->category_name,
            'product_name' => $this->product_name,
            'brand_name' => $this->brand_name,
            'datetime' => $this->datetime,
            'datetime_raw' => $this->datetime_raw,
            'market' => $this->market,
            'currency' => $this->currency,
            'unit_name' => $this->unit_name,
            'price' => $this->price,
            'price_raw' => $this->price_raw,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader', new UserResource($this->uploader)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
