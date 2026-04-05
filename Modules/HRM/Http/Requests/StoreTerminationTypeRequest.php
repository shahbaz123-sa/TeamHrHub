<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTerminationTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:termination_types,name',
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('termination_type.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('termination_type.update');
        }
        return $user && $user->can('termination_type.read');
    }
}
