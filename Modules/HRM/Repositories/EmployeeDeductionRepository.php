<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\EmployeeDeduction;
use Modules\HRM\Contracts\EmployeeDeductionRepositoryInterface;

class EmployeeDeductionRepository implements EmployeeDeductionRepositoryInterface
{
    protected $model;

    public function __construct(EmployeeDeduction $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [], int $perPage = 15)
    {
        return $this->model->with(['employee.department', 'employee.designation', 'employee.branch', 'deduction'])
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereHas('employee', function ($q) use ($filters) {
                    $q->where('name', 'ilike', "%{$filters['q']}%");
                })->orWhereHas('deduction', function ($q) use ($filters) {
                    $q->where('name', 'ilike', "%{$filters['q']}%");
                });
            })
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function find(int $id)
    {
        return $this->model->with(['employee.department', 'employee.designation', 'employee.branch', 'deduction'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->model->findOrFail($id);
        $record->update($data);
        return $record;
    }

    public function delete(int $id)
    {
        $this->model->findOrFail($id)->delete();
    }

    public function getByEmployee(int $employeeId)
    {
        return $this->model->where('employee_id', $employeeId)
            ->with(['deduction'])
            ->get();
    }

    public function getByDeduction(int $deductionId)
    {
        return $this->model->where('deduction_id', $deductionId)
            ->with(['employee.department', 'employee.designation', 'employee.branch'])
            ->get();
    }
}
