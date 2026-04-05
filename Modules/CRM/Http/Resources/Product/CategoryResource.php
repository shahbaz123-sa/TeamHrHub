<?php

namespace Modules\CRM\Http\Resources\Product;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image ? Storage::disk('s3')->url($this->image) : null,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent'),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
