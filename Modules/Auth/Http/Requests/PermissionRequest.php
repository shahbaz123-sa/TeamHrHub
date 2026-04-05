<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($this->route('permission'))
            ],
            'guard_name' => 'sometimes|string|max:255'
        ];

        if ($this->isMethod('POST')) {
            $rules['name'] = 'required|string|max:255|unique:permissions,name';
        }

        return $rules;
    }
}
