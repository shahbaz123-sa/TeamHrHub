<?php

namespace Modules\CRM\Http\Requests\Report;

use Modules\Core\Rules\FileOrPathRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class LatestReportRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('latest_report.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('latest_report.update');
        }
        return $user && $user->can('latest_report.read');
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'attachment' => [
                'nullable',
                new FileOrPathRule(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp']),
                'max:5120'
            ],
            'report_date' => 'required|date',
            'is_active' => 'boolean',
        ];
    }
}
