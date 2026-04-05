<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'avatar' => 'nullable|string',
            'role' => ['required', 'string', Rule::in(['admin', 'author', 'editor', 'maintainer', 'subscriber'])],
            'plan' => ['required', 'string', Rule::in(['basic', 'company', 'enterprise', 'team'])],
            'status' => ['required', 'string', Rule::in(['pending', 'active', 'inactive'])],
            'billing_email' => 'nullable|email',
            'company' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ];
    }
}
