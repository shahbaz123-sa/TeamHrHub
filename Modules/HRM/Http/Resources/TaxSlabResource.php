<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxSlabResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
         return [
             'id' => $this->id,
             'name' => $this->name,
             'min_salary' => (float) $this->min_salary,
             'max_salary' => $this->max_salary === null ? null : (float) $this->max_salary,
             'tax_rate' => (float) $this->tax_rate,
             'fixed_amount' => (float) $this->fixed_amount,
             'exceeding_threshold' => (float) $this->exceeding_threshold,
             'min_salary_monthly' => round(((float)$this->min_salary) / 12, 2),
             'max_salary_monthly' => $this->max_salary === null ? null : round(((float)$this->max_salary) / 12, 2),
             'fixed_amount_monthly' => round(((float)$this->fixed_amount) / 12, 2),
             'exceeding_threshold_monthly' => round(((float)$this->exceeding_threshold) / 12, 2),
             'created_at' => $this->created_at,
             'updated_at' => $this->updated_at,
         ];
    }
}

