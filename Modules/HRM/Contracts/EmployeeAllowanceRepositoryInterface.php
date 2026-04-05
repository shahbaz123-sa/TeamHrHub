<?php

namespace Modules\HRM\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeAllowanceRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator;
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getByEmployee(int $employeeId);
    public function getByAllowance(int $allowanceId);
}
