<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\DependentRepositoryInterface;
use Modules\HRM\Models\Dependent;

class DependentRepository implements DependentRepositoryInterface
{
    protected $model;

    public function __construct(Dependent $model)
    {
        $this->model = $model;
    }

    public function getByEmployee(int $employeeId)
    {
        return $this->model->where('employee_id', $employeeId)->with('employee')->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $dependent = $this->find($id);
        $dependent->update($data);
        return $dependent;
    }

    public function delete(int $id)
    {
        return $this->find($id)->delete();
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }
}
