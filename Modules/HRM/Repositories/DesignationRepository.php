<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\Designation;
use Modules\HRM\Contracts\DesignationRepositoryInterface;

class DesignationRepository implements DesignationRepositoryInterface
{
    protected $model;

    public function __construct(Designation $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->whereAny(['title', 'description'], 'ilike', "%{$filters['q']}%");
                });
            })
            ->latest('created_at')
            ->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $designation = $this->find($id);
        $designation->update($data);
        return $designation;
    }

    public function delete(int $id)
    {
        $designation = $this->find($id);
        return $designation->delete();
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }
}
