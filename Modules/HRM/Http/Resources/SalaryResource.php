<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryResource extends JsonResource
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
            'payslip_type_id' => $this->payslip_type_id,
            'amount' => $this->amount,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'status' => $this->status,
            'net_amount' => $this->amount !== null ? (float)$this->amount - (float)$this->tax_amount : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            'is_tax_applicable' => $this->is_tax_applicable,
            'tax_slab_id' => $this->tax_slab_id,
            'tax_amount' => $this->tax_amount !== null ? (float)$this->tax_amount : null,
            'tax_amount_annual' => $this->tax_amount !== null ? round((float)$this->tax_amount * 12, 2) : null,

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

            // PayslipType relationship
            'payslip_type' => $this->whenLoaded('payslipType', function () {
                return [
                    'id' => $this->payslipType->id,
                    'name' => $this->payslipType->name,
                    'description' => $this->payslipType->description,
                ];
            }),

            'tax_slab' => $this->whenLoaded('taxSlab', function () {
                return [
                    'id' => $this->taxSlab->id,
                    'name' => $this->taxSlab->name,
                    'min_salary_annual' => (float)$this->taxSlab->min_salary,
                    'max_salary_annual' => $this->taxSlab->max_salary === null ? null : (float)$this->taxSlab->max_salary,
                    'min_salary_monthly' => round(((float)$this->taxSlab->min_salary) / 12, 2),
                    'max_salary_monthly' => $this->taxSlab->max_salary === null ? null : round(((float)$this->taxSlab->max_salary) / 12, 2),
                    'tax_rate' => (float)$this->taxSlab->tax_rate,
                    'fixed_amount_annual' => (float)$this->taxSlab->fixed_amount,
                    'fixed_amount_monthly' => round(((float)$this->taxSlab->fixed_amount) / 12, 2),
                    'exceeding_threshold_annual' => (float)$this->taxSlab->exceeding_threshold,
                    'exceeding_threshold_monthly' => round(((float)$this->taxSlab->exceeding_threshold) / 12, 2),
                ];
            }),
        ];
    }
}
