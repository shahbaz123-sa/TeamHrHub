<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('expense_type') ?? $this->route('id');
        $uniqueRule = $id ? "unique:expense_types,name,{$id}" : "unique:expense_types,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('expense_type.update');
    }
}
