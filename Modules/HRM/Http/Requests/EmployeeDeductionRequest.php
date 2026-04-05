<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeDeductionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'deduction_id' => 'required|exists:deductions,id',
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|integer|in:1,2,3',
            'amount' => 'nullable|numeric|min:0',
        ];

        // For updates, make employee_id and deduction_id optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['employee_id'] = 'sometimes|exists:employees,id';
            $rules['deduction_id'] = 'sometimes|exists:deductions,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'deduction_id.required' => 'Deduction is required.',
            'deduction_id.exists' => 'Selected deduction does not exist.',
            'title.string' => 'Title must be a string.',
            'title.max' => 'Title may not be greater than 255 characters.',
            'type.integer' => 'Type must be an integer.',
            'type.in' => 'Selected type is invalid.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be at least 0.',
        ];
    }
}
