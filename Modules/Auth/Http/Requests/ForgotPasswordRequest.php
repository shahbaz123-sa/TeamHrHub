<?php

namespace Modules\Auth\Http\Requests;

use Modules\CRM\Http\Requests\BaseFormRequest;

class ForgotPasswordRequest extends BaseFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'We could not find a user with that email address.',
        ];
    }
}
