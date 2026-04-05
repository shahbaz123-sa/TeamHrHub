<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:expense_types,name',
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('expense_type.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('expense_type.update');
        }
        return $user && $user->can('expense_type.read');
    }
}
