<?php

namespace Modules\HRM\Contracts;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\HRM\Models\EmployeeSalary;

interface SalaryRepositoryInterface
{
    public function paginate(array $filters = []): Paginator;
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function restore($id);
    public function forceDelete($id);
    public function getByEmployee($employeeId);
    public function getCurrentSalary($employeeId);
    public function getHistoryByEmployee($employeeId);
     public function calculateTaxAmount($amount, $taxSlab = null);
    public function refreshTaxForSalary(EmployeeSalary $salary);
}
