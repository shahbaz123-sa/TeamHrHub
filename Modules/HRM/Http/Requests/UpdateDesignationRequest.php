<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HRM\Models\Designation;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('designation.update');
    }

    public function rules()
    {
        $designationId = $this->route('designation')->id;

        return [
            'title' => 'required|string|max:255|unique:designations,title,' . $designationId,
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
