<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDependentRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('dependent.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('dependent.update');
        }
        return $user && $user->can('dependent.read');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'relation' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'age' => 'nullable|integer|min:0'
        ];
    }
}
