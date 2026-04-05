<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanOptionRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('loan_option') ?? $this->route('id');
        $uniqueRule = $id ? "unique:loan_options,name,{$id}" : "unique:loan_options,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('loan_option.update');
    }
}
