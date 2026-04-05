<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DependentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ],
            'name' => $this->name,
            'relation' => $this->relation,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->age,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
