<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee->name,
            'profile_picture' => $this->employee?->user?->avatar_url,
            'employee_official_email' => $this->employee->official_email,
            'employee_personal_email' => $this->employee->personal_email,
            'employee_code' => $this->employee->employee_code,
            'department' => $this->employee->department->name ?? null,
            'designation' => $this->employee->designation->title ?? null,
            'branch' => $this->employee->branch->name ?? null,
            'date' => $this->date->format('Y-m-d'),
            'check_in' => $this->check_in?->format('H:i:s'),
            'check_out' => $this->check_out?->format('H:i:s'),
            'address_in' => $this->check_in_other_location,
            'address_out' => $this->check_out_other_location,
            'latitude_in' => $this->latitude_in,
            'longitude_in' => $this->longitude_in,
            'latitude_out' => $this->latitude_out,
            'longitude_out' => $this->longitude_out,
            'location_in' => $this->check_in_address,
            'location_out' => $this->check_out_address,
            'status' => $this->status_label,
            'late_minutes' => $this->late_minutes,
            'early_check_in_minutes' => $this->early_check_in_minutes,
            'early_leaving_minutes' => $this->early_leaving_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'check_in_from' => $this->check_in_from,
            'check_out_from' => $this->check_out_from,
            'note' => $this->note,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
