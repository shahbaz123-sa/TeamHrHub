<?php

namespace Modules\HRM\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TaxSlabExport implements FromView
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('hrm::payroll.tax-slab-excel', $this->data);
    }
}

