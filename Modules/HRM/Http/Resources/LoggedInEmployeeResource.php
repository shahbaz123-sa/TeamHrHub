<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoggedInEmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            // Personal Details
            'name' => $this->tokenable->name,
            'profile_picture' => $this->tokenable->employee->user?->avatar_url ?? null,
            'department' => $this->tokenable->employee->department?->name ?? 'N/A',
            // Company Details
            'employee_code' => $this->tokenable->employee?->employee_code ?? 'N/A',
            'reporting_to' => [
                'name' => $this->tokenable->employee->reportingTo?->name ?? 'N/A',
                ],
            'last_login_at' => $this->created_at,
            'location' => $this->loginActivity?->location,
            'browser' => $this->loginActivity?->user_agent,
            "device_type" => $this->loginActivity?->device_type,
            // Relationships
            'user' => [
                'id' => $this->tokenable->id,
                'name' => $this->tokenable->name,
                'email' => $this->tokenable->email,
                'roles' => $this->when(
                    $this->tokenable->employee?->relationLoaded('user')
                    && $this->tokenable?->relationLoaded('roles'),
                    fn () => $this->tokenable->roles->pluck('name')->values(),
                    fn () => collect() // or null if you prefer
                ),
            ],
        ];
    }
}
