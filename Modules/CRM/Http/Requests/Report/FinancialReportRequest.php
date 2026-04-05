<?php

namespace Modules\CRM\Http\Requests\Report;

use Modules\Core\Rules\FileOrPathRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class FinancialReportRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('financial_report.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('financial_report.update');
        }
        return $user && $user->can('financial_report.read');
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'report_date' => 'required|date',
            'press_release' => [
                'nullable',
                new FileOrPathRule(['pdf']),
                'max:819200'
            ],
            'financial_report' => [
                'nullable',
                new FileOrPathRule(['pdf']),
                'max:819200'
            ],
            'presentation' => [
                'nullable',
                new FileOrPathRule(['pdf']),
                'max:819200'
            ],
            'transcript' => [
                'nullable',
                new FileOrPathRule(['pdf']),
                'max:819200'
            ],
            'video' => [
                'nullable',
                new FileOrPathRule(['mp4', 'mov', 'avi', 'mkv', 'webm']),
                'max:100000'
            ],
            'is_active' => 'boolean',
        ];
    }
}
