<?php

namespace Modules\CRM\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CRM\Http\Resources\Product\Attribute\ValueResource;

class VariationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'parent_id' => $this->parent_id,
            'uom_id' => $this->uom_id,
            'quantity' => $this->quantity,
            'min_quantity' => $this->min_quantity,
            'max_quantity' => $this->max_quantity,
            'attributes' => $this->whenLoaded('attributes', AttributeResource::collection($this->attributes)),
            'attributes_values' => $this->whenLoaded('attributeValues', ValueResource::collection($this->attributeValues)),
            'city_wise_prices' => $this->whenLoaded('prices'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
