<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->user,
            'password' => 'sometimes|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg',
            'role' => ['sometimes', 'string', Rule::in(['admin', 'author', 'editor', 'maintainer', 'subscriber'])],
            'plan' => ['sometimes', 'string', Rule::in(['basic', 'company', 'enterprise', 'team'])],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'active', 'inactive'])],
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

        // If password is being updated, require current password
        if ($this->has('password')) {
            $rules['current_password'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, auth()->user()->password)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'current_password.required' => 'Current password is required when changing password.',
            'password.min' => 'New password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'avatar.image' => 'The avatar must be a valid image file.',
            'avatar.mimes' => 'The avatar must be a file of type: PNG, JPEG, JPG.',
        ];
    }
}
