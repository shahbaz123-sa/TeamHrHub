<?php

namespace Modules\CRM\Http\Resources\Order;

use Carbon\Carbon;
use Modules\CRM\Http\Resources\OrderResource;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order' => $this->whenLoaded('order', new OrderResource($this->order)),
            'status' => $this->status,
            'history_datetime' => Carbon::parse($this->created_at)->format('D, M d, Y \a\t g:i A'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
