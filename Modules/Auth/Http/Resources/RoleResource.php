<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        $assignableRoles = [];
        if ($this->assignableRoles) {
            $assignableRoles = $this->assignableRoles->pluck('id');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'permissionNames' => $this->getPermissionNames(),
            'permissions' => $this->whenLoaded('permissions', PermissionResource::collection($this->permissions)),
//            'assignable_role_ids' => $assignableRoles,
            'assignable_role_ids' => $this->relationLoaded('assignableRoles')
                ? $this->assignableRoles->pluck('id')->values()
                : [],
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
