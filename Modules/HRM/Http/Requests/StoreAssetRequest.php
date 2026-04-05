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
            'asset_type_id' => 'required|integer',
            'name' => 'required|string',
            'serial_no' => 'required|string',
            'amount' => 'required|integer',
            'purchase_date' => 'required|date',
            'support_until' => 'nullable|date',
            'description' => 'nullable|string',
        ];
    }
}
