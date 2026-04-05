<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\Deduction;
use Modules\HRM\Contracts\DeductionRepositoryInterface;

class DeductionRepository implements DeductionRepositoryInterface
{
    protected $model;

    public function __construct(Deduction $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where('name', 'ilike', "%{$filters['q']}%");
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
        $deduction = $this->find($id);
        $deduction->update($data);
        return $deduction;
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
