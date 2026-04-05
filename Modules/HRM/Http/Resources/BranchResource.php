<?php

namespace Modules\HRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'grace_period' => $this->grace_period,
            'attendance_radius' => $this->attendance_radius,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'office_start_time' => $this->office_start_time ? Carbon::parse($this->office_start_time)->format('H:i') : "",
            'office_end_time' => $this->office_end_time ? Carbon::parse($this->office_end_time)->format('H:i') : "",
            'allow_remote' => $this->allow_remote,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'time_deviations' => $this->time_deviations,
        ];
    }
}
