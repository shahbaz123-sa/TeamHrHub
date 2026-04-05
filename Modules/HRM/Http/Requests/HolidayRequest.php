<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class HolidayRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('holidays.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('holidays.update');
        }
        return $user && $user->can('holidays.read');
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'required|boolean',
            'description' => 'nullable|string',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // For updates, we might want to allow the same name for different dates
            // or add additional validation rules
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Holiday name is required',
            'name.max' => 'Holiday name must not exceed 255 characters',
            'date.required' => 'Holiday date is required',
            'date.date' => 'Please provide a valid date',
            'is_recurring.required' => 'Please specify if this holiday is recurring',
            'is_recurring.boolean' => 'Recurring field must be true or false',
        ];
    }
}

