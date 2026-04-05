<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketStatsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'department' => $this->department->name,
            'open' => $this->where('status', 'Open')->count(),
            'pending' => $this->where('status', 'Pending')->count(),
            'resolved' => $this->where('status', 'Resolved')->count(),
            'total' => $this->count(),
        ];
    }
}
