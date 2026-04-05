<?php

namespace Modules\CRM\Http\Requests;

use Modules\CRM\Http\Requests\BaseFormRequest;

class CustomerRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('order.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('order.update');
        }
        return $user && $user->can('order.read');
    }

    public function rules()
    {
        return [];
    }
}
