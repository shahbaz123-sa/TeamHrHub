<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignationRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('designation.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('designation.update');
        }
        return $user && $user->can('designation.read');
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255|unique:designations,title',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title field is required.',
            'title.unique' => 'This designation title already exists.',
        ];
    }
}
