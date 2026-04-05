<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\PayrollDeduction;
use Modules\HRM\Contracts\PayrollDeductionRepositoryInterface;

class PayrollDeductionRepository implements PayrollDeductionRepositoryInterface
{
    protected $model;

    public function __construct(PayrollDeduction $model)
    {
        $this->model = $model;
    }

    public function paginate($request)
    {
        return $this->model->orderBy('created_at', 'desc')->paginate($request['per_page']);
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function updateStatus($id)
    {
        $deduction = $this->find($id);
        $deduction->is_enabled = !$deduction->is_enabled;
        $deduction->save();
        return $deduction;
    }
}

