<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\EmployeeLoan;
use Modules\HRM\Contracts\EmployeeLoanRepositoryInterface;

class EmployeeLoanRepository implements EmployeeLoanRepositoryInterface
{
    protected $model;

    public function __construct(EmployeeLoan $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [], int $perPage = 15)
    {
        return $this->model->with(['employee.department', 'employee.designation', 'employee.branch', 'loan'])
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereHas('employee', function ($q) use ($filters) {
                    $q->where('name', 'ilike', "%{$filters['q']}%");
                })->orWhereHas('loan', function ($q) use ($filters) {
                    $q->where('name', 'ilike', "%{$filters['q']}%");
                });
            })
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function find(int $id)
    {
        return $this->model->with(['employee.department', 'employee.designation', 'employee.branch', 'loan'])->findOrFail($id);
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
            ->with(['loan'])
            ->get();
    }

    public function getByLoan(int $loanId)
    {
        return $this->model->where('loan_id', $loanId)
            ->with(['employee.department', 'employee.designation', 'employee.branch'])
            ->get();
    }
}
