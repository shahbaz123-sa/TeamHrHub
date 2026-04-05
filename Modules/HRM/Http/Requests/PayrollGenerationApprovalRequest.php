<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollGenerationApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'scope' => 'required|in:hr,ceo',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'approved' => 'required|boolean',
        ];
    }
}

