<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'leave_reason' => $this->leave_reason,
            'duration_type' => $this->duration_type,
            'duration_type_name' => $this->duration_type_name,
            'days' => $this->days,
            'leave_days' => $this->leave_days,
            'leave_attachment' => $this->leave_attachment,
            'leave_attachment_url' => $this->leave_attachment ? Storage::disk('s3')->url($this->leave_attachment) : null,
            'manager_status' => $this->manager_status,
            'hr_status' => $this->hr_status,
            'is_paid' => (bool) $this->is_paid,
            'total_paid_days' => $this->total_paid_days,
            'total_unpaid_days' => $this->total_unpaid_days,
            'paid_start_date' => optional($this->paid_start_date)?->toDateString(),
            'paid_end_date' => optional($this->paid_end_date)?->toDateString(),
            'unpaid_start_date' => optional($this->unpaid_start_date)?->toDateString(),
            'unpaid_end_date' => optional($this->unpaid_end_date)?->toDateString(),
            'employee' => new EmployeeResource($this->employee),
            'leave_type' => new LeaveTypeResource($this->leaveType),
            'applied_on' => $this->created_at?->toDateString(),
            'applied_on_timestamp' => $this->created_at?->toDateTimeString(),
        ];
    }
}
