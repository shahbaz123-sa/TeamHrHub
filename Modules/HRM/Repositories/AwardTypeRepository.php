<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\AwardType;
use Modules\HRM\Contracts\AwardTypeRepositoryInterface;

class AwardTypeRepository implements AwardTypeRepositoryInterface
{
    protected $model;

    public function __construct(AwardType $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = [])
    {
        return $this->model
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where('name', 'ilike', "%{$filters['q']}%");
            })
            ->latest('created_at')
            ->get();
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
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
}
