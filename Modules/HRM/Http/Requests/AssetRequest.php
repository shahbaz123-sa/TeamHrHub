<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('asset.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('asset.update');
        }
        return $user && $user->can('asset.read');
    }

    public function rules()
    {
        return [
            'asset_type_id' => 'required|exists:asset_types,id',
            'name' => 'required|string|max:255',
            'serial_no' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'make_model' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'attributes' => 'nullable|array',
            'attributes.*' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'asset_type_id.required' => 'The asset type is required.',
            'asset_type_id.exists' => 'The selected asset type does not exist.',
            'name.required' => 'The asset name is required.',
            'name.max' => 'The asset name may not be greater than 255 characters.',
            'serial_no.required' => 'The serial number is required.',
            'serial_no.max' => 'The serial number may not be greater than 255 characters.',
            'purchase_date.required' => 'The purchase date is required.',
            'purchase_date.date' => 'The purchase date must be a valid date.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ];
    }
}
