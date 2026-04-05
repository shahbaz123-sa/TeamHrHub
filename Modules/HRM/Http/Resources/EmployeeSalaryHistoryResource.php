<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSalaryHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_salary_id' => $this->employee_salary_id,
            'action' => $this->action,
            'amount' => $this->amount,
            'tax_amount' => $this->tax_amount,
            'tax_amount_annual' => $this->tax_amount !== null ? round((float)$this->tax_amount * 12, 2) : null,
            'is_tax_applicable' => $this->is_tax_applicable,
            'tax_slab_id' => $this->tax_slab_id,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'status' => $this->status,
            'payload' => $this->payload,
            'performed_by' => $this->performed_by,
            'performed_by_user' => $this->whenLoaded('performedBy', function () {
                return [
                    'id' => $this->performedBy->id,
                    'name' => $this->performedBy->name,
                    'email' => $this->performedBy->email,
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
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

