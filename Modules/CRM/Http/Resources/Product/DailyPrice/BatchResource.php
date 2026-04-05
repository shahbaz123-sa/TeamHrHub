<?php

namespace Modules\CRM\Http\Resources\Product\DailyPrice;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class BatchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'batch' => Str::of($this->id)->padLeft(3, '0'),
            'status' => strtolower($this->status),
            'status_formatted' => Str::title($this->status),
            'price_date' => $this->price_date,
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'approver' => $this->whenLoaded('approver', fn() => $this->approver->name),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejecter' => $this->whenLoaded('rejecter', fn() => $this->rejecter->name),
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at,
            'creater' => $this->whenLoaded('creater', fn() => $this->creater->name),
            'created_by' => $this->created_by,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
