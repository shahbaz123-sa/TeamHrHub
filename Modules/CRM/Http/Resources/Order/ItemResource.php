<?php

namespace Modules\CRM\Http\Resources\Order;

use Modules\CRM\Http\Resources\OrderResource;
use Modules\CRM\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CRM\Http\Resources\Product\VariationResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order_id' => $this->order_id,
            'order' => $this->whenLoaded('order', new OrderResource($this->order)),
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', new ProductResource($this->product)),
            'variation_id' => $this->variation_id,
            'variation' => $this->whenLoaded('variation', new VariationResource($this->variation)),
            'city_id' => $this->city_id,
            'quantity' => $this->quantity,
            'uom_name' => $this->uom_name,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
