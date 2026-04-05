<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLoanResource extends JsonResource
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
            'loan_id' => $this->loan_id,
            'title' => $this->title,
            'type' => $this->type,
            'reasons' => $this->reasons,
            'amount' => $this->amount,
            'status' => $this->status,
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
            
            // Loan relationship
            'loan' => $this->whenLoaded('loan', function () {
                return [
                    'id' => $this->loan->id,
                    'name' => $this->loan->name,
                    'description' => $this->loan->description,
                ];
            }),
        ];
    }
}
