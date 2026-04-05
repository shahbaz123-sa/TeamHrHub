<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('role.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('role.update');
        }
        return $user && $user->can('role.read');
    }

    public function rules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->route('role'))
            ],
            'guard_name' => 'sometimes|string|max:255|in:web,api',
            'permissions' => 'required|array',
            'permissions.*.id' => 'required|exists:permissions,id',
            'permissions.*.name' => 'required|string|exists:permissions,name',
            'allowed_role_ids' => 'nullable',
        ];

        if ($this->isMethod('POST')) {
            $rules['name'] = 'required|string|max:255|unique:roles,name';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'permissions.required' => 'At least one permission must be selected',
            'permissions.*.id.required' => 'Permission ID is required',
            'permissions.*.id.exists' => 'Invalid permission ID',
            'permissions.*.name.required' => 'Permission name is required',
            'permissions.*.name.exists' => 'Invalid permission name',
            'permissions.*.read.required' => 'Read permission flag is required',
            'permissions.*.write.required' => 'Write permission flag is required',
            'permissions.*.create.required' => 'Create permission flag is required',
        ];
    }
}
