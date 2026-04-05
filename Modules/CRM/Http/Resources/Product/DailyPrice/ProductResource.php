<?php

namespace Modules\CRM\Http\Resources\Product\DailyPrice;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CRM\Http\Resources\Product\CityResource;
use Modules\CRM\Http\Resources\ProductResource as CrmProductResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'batch' => new BatchResource($this->whenLoaded('batch')),
            'product_id' => $this->product_id,
            'product' => new CrmProductResource($this->whenLoaded('product')),
            'city_id' => $this->city_id,
            'city' => new CityResource($this->whenLoaded('city')),
            'product_sku' => $this->product_sku,
            'category' => $this->category,
            'sub_category' => $this->sub_category,
            'brand' => $this->brand,
            'old_product_name' => $this->old_product_name,
            'new_product_name' => $this->new_product_name,
            'new_variant_name' => $this->new_variant_name,
            'old_city' => $this->old_city,
            'city' => $this->city,
            'province' => $this->province,
            'new_city' => $this->new_city,
            'vendor_name' => $this->vendor_name,
            'vendor_price' => $this->vendor_price,
            'zarea_price' => $this->zarea_price,
            'old_delivered_price' => $this->old_delivered_price,
            'new_delivered_price' => $this->new_delivered_price,
            'min_order_qty' => $this->min_order_qty,
            'price_bulk_qty' => $this->price_bulk_qty,
            'zarea_price_on_bulk' => $this->zarea_price_on_bulk,
            'comments' => $this->comments,
            'is_graph_product' => ucfirst($this->is_graph_product),
            'graph_category' => $this->graph_category,
            'graph_product' => $this->graph_product,
            'graph_product_unit' => $this->graph_product_unit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
