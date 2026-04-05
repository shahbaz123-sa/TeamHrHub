<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalaryRequest extends FormRequest
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
            'payslip_type_id' => 'required|exists:payslip_types,id',
            'amount' => 'required|numeric|min:0',
            'is_tax_applicable' => 'boolean',
            'tax_slab_id' => 'nullable|exists:tax_slabs,id',
            'effective_date' => 'nullable|date',
            'status' => 'boolean',
        ];

        // For updates, make employee_id optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['employee_id'] = 'sometimes|exists:employees,id';
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
            'payslip_type_id.required' => 'Payslip type is required.',
            'payslip_type_id.exists' => 'Selected payslip type does not exist.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be at least 0.',
            'effective_date.date' => 'Effective date must be a valid date.',
            'status.boolean' => 'Status must be true or false.',
        ];
    }
}
