<?php

namespace Modules\CRM\Http\Requests;

use Modules\Core\Rules\SlugRegexRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class EmailSettingRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('email_setting.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('email_setting.update');
        }
        return $user && $user->can('email_setting.read');
    }

    public function rules()
    {
        return [
            'notify_on' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                new SlugRegexRule(),
                'unique:crm.email_settings,slug,' . $this->route('email_setting', 0)
            ],
            'recipients' => 'required|string|max:500',
            'is_active' => 'boolean',
        ];
    }
}
