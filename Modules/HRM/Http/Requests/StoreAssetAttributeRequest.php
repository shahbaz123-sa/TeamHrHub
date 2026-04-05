<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetAttributeRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'field_type' => ['required', 'string', Rule::in(['string', 'number', 'date', 'boolean', 'select'])],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The attribute name is required.',
            'name.max' => 'The attribute name may not be greater than 255 characters.',
            'asset_type_id.required' => 'The asset type is required.',
            'asset_type_id.exists' => 'The selected asset type does not exist.',
            'field_type.required' => 'The field type is required.',
            'field_type.in' => 'The field type must be one of: text, number, date, boolean, select.',
            'options.array' => 'The options must be an array.',
            'options.*.string' => 'Each option must be a string.',
            'options.*.max' => 'Each option may not be greater than 255 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert JSON string to array if needed
        if ($this->has('options') && is_string($this->options)) {
            try {
                $this->merge([
                    'options' => json_decode($this->options, true)
                ]);
            } catch (\Exception $e) {
                // Keep as string if JSON decode fails
            }
        }
    }
}

