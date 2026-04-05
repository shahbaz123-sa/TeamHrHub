<?php

namespace Modules\CRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $category = $this->categories()->orderByPivot('updated_at', 'desc')->first();

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'slug' => $this->wc_slug,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'stock_status' => $this->stock_status,
            'ask_for_quote' => $this->ask_for_quote,
            'price' => $this->price,
            'regular_price' => $this->regular_price,
            'sale_price' => $this->sale_price,
            'uom_id' => $this->uom_id,
            'uom' => $this->whenLoaded('uom'),
            'brand_id' => $this->brand_id,
            'brand' => $this->whenLoaded('brand'),
            'images' => $this->whenLoaded('images'),
            'image' => $this->images->first(),
            'tags' => $this->whenLoaded('tags', Product\TagResource::collection($this->tags)),
            'category_parent_id' => data_get($category, 'parent_id'),
            'categories' => $this->whenLoaded('categories', Product\CategoryResource::collection($this->categories)),
            'category' => $category,
            'variations' => $this->whenLoaded('variations', Product\VariationResource::collection($this->variations)),
            'city_wise_prices' => $this->whenLoaded('prices'),
            'quantity' => $this->quantity,
            'min_quantity' => $this->min_quantity,
            'max_quantity' => $this->max_quantity,
            'weight' => $this->weight,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
