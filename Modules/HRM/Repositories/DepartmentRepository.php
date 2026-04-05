<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\DepartmentRepositoryInterface;
use Modules\HRM\Models\Department;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    protected $model;

    public function __construct(Department $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'ilike', "%{$filters['q']}%")
                        ->orWhere('description', 'ilike', "%{$filters['q']}%");
                });
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(
                isset($filters['context']) && $filters['context'] === 'filters',
                function ($query) {
                    $query->where('status', true);
                }
            )
            ->latest('created_at')
            ->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $department = $this->find($id);
        if (($data['status'] ?? null) == 0) {
            $count = $department->employees()->count();
            if ($count > 0) {
                $label = $count === 1 ? 'employee' : 'employees';
                throw new \Exception(
                    "This department has {$count} assigned {$label}. Reassign them first."
                );
            }
        }
        $department->update($data);
        return $department;
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
