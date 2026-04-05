<?php

namespace Modules\CRM\Http\Resources\Product\Attribute;

use Illuminate\Http\Resources\Json\JsonResource;

class ValueResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'attribute_id' => $this->attribute_id,
            'attribute' => $this->whenLoaded('attribute'),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
