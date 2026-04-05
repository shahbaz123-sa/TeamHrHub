<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:job_categories,name',
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('job_category.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('job_category.update');
        }
        return $user && $user->can('job_category.read');
    }
}
