<?php

namespace Modules\HRM\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'today_stats' => $this['today_stats'],
            'this_month_stats' => $this['this_month_stats'],
        ];
    }
}
