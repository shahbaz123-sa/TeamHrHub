<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Contracts\BranchRepositoryInterface;
use Modules\HRM\Models\Branch;

class BranchRepository implements BranchRepositoryInterface
{
    protected $model;

    public function __construct(Branch $model)
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
        $branch = $this->find($id);
        $branch->update($data);
        return $branch;
    }

    public function delete(int $id)
    {
        return $this->find($id)->delete();
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function allowRemote()
    {
        return Branch::where('id', '<>', 0)->update([
            'allow_remote' => true,
        ]);
    }

    public function disallowRemote()
    {
        return Branch::where('id', '<>', 0)->update([
            'allow_remote' => false,
        ]);
    }
}
