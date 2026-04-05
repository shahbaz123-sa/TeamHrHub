<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeAllowanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'allowance_id' => $this->allowance_id,
            'title' => $this->title,
            'type' => $this->type,
            'amount' => $this->amount,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Employee relationship
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'employee_code' => $this->employee->employee_code,
                    'name' => $this->employee->name,
                    'department' => $this->employee->department ? [
                        'id' => $this->employee->department->id,
                        'name' => $this->employee->department->name,
                    ] : null,
                    'designation' => $this->employee->designation ? [
                        'id' => $this->employee->designation->id,
                        'title' => $this->employee->designation->title,
                    ] : null,
                    'branch' => $this->employee->branch ? [
                        'id' => $this->employee->branch->id,
                        'name' => $this->employee->branch->name,
                    ] : null,
                ];
            }),
            
            // Allowance relationship
            'allowance' => $this->whenLoaded('allowance', function () {
                return [
                    'id' => $this->allowance->id,
                    'name' => $this->allowance->name,
                    'description' => $this->allowance->description,
                ];
            }),
        ];
    }
}
