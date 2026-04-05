<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HRM\Http\Resources\EmployeeResource;
use Modules\HRM\Http\Resources\DepartmentResource;
use Modules\HRM\Http\Resources\TicketCategoryResource;

class TicketResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'ticket_code'  => $this->ticket_code,
            'employee'     => new EmployeeResource($this->whenLoaded('employee')),
            'profile_picture' => $this->employee?->user?->avatar_url,
            'department'   => new DepartmentResource($this->whenLoaded('department')),
            'poc'          => new EmployeeResource($this->whenLoaded('poc')),
            'category'     => new TicketCategoryResource($this->whenLoaded('category')),
            'description'  => $this->description,
            'attachment'   => filled($this->attachment) ? Storage::disk('s3')->url($this->attachment) : null,
            'status'       => $this->status,
            'start_date'   => $this->start_date,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
