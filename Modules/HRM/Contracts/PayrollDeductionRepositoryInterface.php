<?php

namespace Modules\HRM\Contracts;

interface PayrollDeductionRepositoryInterface
{
    public function paginate($request);
    public function find($id);
    public function updateStatus($id);
}

