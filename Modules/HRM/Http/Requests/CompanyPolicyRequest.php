<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('company_policy.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('company_policy.update');
        }
        return $user && $user->can('company_policy.read');
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function rules(): array
    {
        $rules = [
            'branch_id' => ['required', 'exists:branches,id'],
            'display_order' => ['required', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'attachment' => ['required', 'file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:20480'],
        ];

        if (request()->routeIs('company-policies.update')) {
            $rules['attachment'] = ['nullable', 'file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:20480'];
        }

        return $rules;
    }
}
