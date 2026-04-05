<?php

namespace Modules\HRM\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SalaryGenerationExport implements FromView
{
    public function __construct(protected array $data)
    {
    }

    public function view(): View
    {
        return view('hrm::payroll.salary-generation-excel', $this->data);
    }
}

