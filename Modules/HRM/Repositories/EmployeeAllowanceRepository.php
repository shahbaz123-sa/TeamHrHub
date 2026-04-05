<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\EmployeeAllowance;
use Modules\HRM\Contracts\EmployeeAllowanceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeAllowanceRepository implements EmployeeAllowanceRepositoryInterface
{
    protected $model;

    public function __construct(EmployeeAllowance $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['employee', 'allowance']);

        // Apply filters
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['allowance_id'])) {
            $query->where('allowance_id', $filters['allowance_id']);
        }

        if (isset($filters['q'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('name', 'ilike', "%{$filters['q']}%");
            })->orWhereHas('allowance', function ($q) use ($filters) {
                $q->where('name', 'ilike', "%{$filters['q']}%");
            });
        }

        return $query->latest('created_at')->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id)
    {
        return $this->model->with(['employee', 'allowance'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $record = $this->model->findOrFail($id);
        $record->update($data);
        return $record->load(['employee', 'allowance']);
    }

    public function delete(int $id)
    {
        $this->model->findOrFail($id)->delete();
    }

    public function getByEmployee(int $employeeId)
    {
        return $this->model->where('employee_id', $employeeId)
            ->with(['allowance'])
            ->get();
    }

    public function getByAllowance(int $allowanceId)
    {
        return $this->model->where('allowance_id', $allowanceId)
            ->with(['employee'])
            ->get();
    }
}
