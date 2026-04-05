<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\JobStage;
use Modules\HRM\Contracts\JobStageRepositoryInterface;

class JobStageRepository implements JobStageRepositoryInterface
{
    protected $model;

    public function __construct(JobStage $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->latest('created_at')->get();
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
